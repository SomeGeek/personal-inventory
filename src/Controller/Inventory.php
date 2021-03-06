<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\ODM\MongoDB\DocumentManager;

use App\Document\InventoryItem;
use App\Document\Tag;
use App\Service\ImageStorage;
use App\Service\FileStorage;
use App\Service\InventoryItemService;
use Symfony\Component\HttpFoundation\Response;

class Inventory extends AbstractController
{
    /** @var ImageStorage */
    protected $images;

    /** @var FileStorage */
    protected $files;

    /** @var InventoryItemService */
    protected $InventoryItemService;

    public function __construct(DocumentManager $dm, ImageStorage $images, FileStorage $files, InventoryItemService $InventoryItemService)
    {
        $this->dm = $dm;
        $this->inventoryRepo = $this->dm->getRepository(InventoryItem::class);
        $this->tagRepo = $this->dm->getRepository(Tag::class);
        $this->images = $images;
        $this->files = $files;
        $this->inventoryItemService = $InventoryItemService;

        // Create the schema and indexes in MongoDB
        $this->dm->getSchemaManager()->ensureIndexes();
    }

    public function listItems(Request $request, string $category = null, string $tag = null)
    {
        $breadcrumb = '';
        if ($category && $tag) {
            $items = $this->inventoryItemService->findByCategoryAndTag($category, $tag);
            $breadcrumb = $tag;
        } elseif ($query = $request->query->get('q', '')) {
            $items = $this->inventoryItemService->searchInventoryItems($query);
            $breadcrumb = $query;
        } else {
            $items = $this->inventoryItemService->getAll();
        }
        return $this->render(
            'inventory/list.html.twig',
            [
                'items' => $items,
                'breadcrumb' => $breadcrumb
            ]
        );
    }

    public function getItem($id)
    {
        $item = $this->inventoryRepo->findOneBy(['id' => $id]);
        if (!$item) {
            throw $this->createNotFoundException('Item not found');
        }
        return $this->render(
            'inventory/view.html.twig',
            ['item' => $item, 'images' => $this->images->getItemImages($item), 'files' => $this->files->getItemFiles($item)]
        );
    }

    public function editItem(Request $request, $id = null)
    {
        $errors = [];
        if ($id) {
            $item = $this->inventoryRepo->findOneBy(['id' => $id]);
            if (!$item) {
                throw $this->createNotFoundException('Item not found');
            }
            $images = $this->images->getItemImages($item);
            $files = $this->files->getItemFiles($item);
            $originalLocations = $item->getLocations();
            $originalTypes = $item->getTypes();
            $originalStates = $item->getStates();
            $mode = 'edit';
        } else {
            $item = new InventoryItem();
            $images = [];
            $files = [];
            $originalLocations = [];
            $originalTypes = [];
            $originalStates = [];
            $mode = 'new';
        }

        // Handle delete
        if ($request->isMethod('POST') && $request->request->get('submit', 'submit') === 'delete') {
            $this->inventoryItemService->deleteInventoryItem($item);
            $this->dm->flush();
            return $this->redirectToRoute('inventory_list');
        }

        $form = $this->getItemForm($request, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item = $form->getData();
            try {
                // Save tags
                $this->saveTags(Tag::CATEGORY_ITEM_TYPE, $item->getTypes());
                $this->saveTags(Tag::CATEGORY_ITEM_LOCATION, $item->getLocations());
                $this->saveTags(Tag::CATEGORY_ITEM_STATE, $item->getStates());
                $id = $this->inventoryItemService->saveInventoryItem($item, $originalLocations, $originalTypes, $originalStates);
                $this->images->saveItemImages($item, $request->files->get('form')['images']);
                $this->files->saveItemfiles($item, $request->files->get('form')['files']);
                $this->deleteImages($request, $item);
                $this->deleteFiles($request, $item);
                $this->dm->flush();
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
            if (!$errors) {
                if ($request->request->get('submit', 'submit') === 'submit_add') {
                    return $this->redirectToRoute('inventory_add');
                } elseif ($request->query->get('return_to', '') === 'list') {
                    return $this->redirectToRoute('inventory_list');
                } else {
                    return $this->redirectToRoute('inventory_get', ['id' => $id]);
                }
            }
        }

        return $this->render(
            'inventory/edit.html.twig',
            [
                'form' => $form->createView(),
                'mode' => $mode,
                'itemid' => $item->getId(),
                'images' => $images,
                'files' => $files,
                'errors' => $errors
            ]
        );
    }

    private function getItemForm(Request $request, InventoryItem $item)
    {
        $tagAttributes = [
            'attr' => ['class' => 'tags'],
            'expanded' => false,
            'help' => 'Hit enter or comma to create new tags',
            'multiple' => true,
            'required' => false
        ];

        return $this->createFormBuilder($item)
            ->add('name', TextType::class)
            ->add('quantity', IntegerType::class)
            ->add('manufacturer', TextType::class, ['required' => false])
            ->add('model', TextType::class, ['required' => false])
            ->add('serialNumbers', TextareaType::class, ['required' => false])
            ->add(
                'purchasePrice',
                MoneyType::class,
                // TODO: Make currency configurable
                ['label' => 'Purchase price (per item)', 'required' => false, 'currency' => 'USD']
            )
            ->add(
                'value',
                MoneyType::class,
                // TODO: Make currency configurable
                ['label' => 'Current value (per item)', 'required' => false, 'currency' => 'USD']
            )
            ->add(
                'types',
                ChoiceType::class,
                [
                    'label' => 'Type / Tags',
                    'choices' => $this->getTags($request, 'types', Tag::CATEGORY_ITEM_TYPE),
                ] + $tagAttributes
            )
            ->add(
                'locations',
                ChoiceType::class,
                [
                    'label' => 'Location(s)',
                    'choices' => $this->getTags($request, 'locations', Tag::CATEGORY_ITEM_LOCATION),
                ] + $tagAttributes
            )
            ->add(
                'states',
                ChoiceType::class,
                [
                    'label' => 'State(s)',
                    'choices' => $this->getTags($request, 'states', Tag::CATEGORY_ITEM_STATE),
                ] + $tagAttributes
            )
            ->add(
                'acquiredDate',
                DateType::class,
                [
                    'label' => 'Date Acquired',
                    'widget' => 'single_text',
                    'required' => false
                ]
            )
            ->add(
                'notes',
                TextareaType::class,
                ['required' => false]
            )
            ->add(
                'images',
                FileType::class,
                [
                    'label' => 'Add Images',
                    'multiple' => true,
                    'mapped' => false,
                    'required' => false,
                    'attr' => ['accept' => 'image/*']
                ]
            )->add(
                'files',
                FileType::class,
                [
                    'label' => 'Add Files',
                    'multiple' => true,
                    'mapped' => false,
                    'required' => false,
                ]
            )
            ->getForm();
    }

    /**
     * Get tags, including any new tags POSTed through the form
     *
     * @param Request $request HTTP request
     * @param string $field Form and entity field name
     * @param string $tagCategory
     * @return string[]
     */
    private function getTags(Request $request, $field, $tagCategory)
    {
        $tags = [];
        if ($request->getMethod() === 'POST') {
            $formInput = $request->request->get('form');
            if (array_key_exists($field, $formInput)) {
                $tags = array_combine($formInput[$field], $formInput[$field]);
            }
        }
        foreach ($this->tagRepo->findBy(['category' => $tagCategory]) as $tag) {
            $tags[(string) $tag] = (string) $tag;
        }
        return $tags;
    }

    /**
     * Delete images from form POST
     *
     * @param Request $request
     * @param InventoryItem $item
     */
    private function deleteImages(Request $request, InventoryItem $item)
    {
        $formInput = $request->request->get('delete_images');
        if ($formInput) {
            foreach ($formInput as $filename) {
                $this->images->deleteItemImage($item, $filename);
            }
        }
    }

    /**
     * Delete files from form POST
     *
     * @param Request $request
     * @param InventoryItem $item
     */
    private function deleteFiles(Request $request, InventoryItem $item)
    {
        $formInput = $request->request->get('delete_files');
        if ($formInput) {
            foreach ($formInput as $filename) {
                $this->files->deleteItemFile($item, $filename);
            }
        }
    }

    /**
     * GET image content; POST to delete
     *
     * Query string parameters "w" and "h" can be used to get a scaled version. Original images will be scaled as needed.
     */
    public function image(Request $request, $id, $filename)
    {
        $item = $this->inventoryRepo->findOneBy(['id' => $id]);
        #$item = $this->docs->getInventoryItem($id);
        if (!$item) {
            throw $this->createNotFoundException('Item not found');
        }
        if ($request->getMethod() === 'POST' && $request->request->get['action'] === 'delete') {
            $this->images->deleteItemImage($item, $filename);
            return new JsonResponse(['success' => 1]);
        } else {
            $path = $this->images->getFilePath($item, $filename, $request->query->get('w'), $request->query->get('h'));
            if (file_exists($path)) {
                return new BinaryFileResponse($path, Response::HTTP_OK, ['Cache-Control' => 'max-age=14400']);
            } else {
                throw $this->createNotFoundException('Image not found');
            }
        }
    }

    /**
     * GET file content; POST to delete
     *
     */
    public function itemfile(Request $request, $id, $filename)
    {
        $item = $this->inventoryRepo->findOneBy(['id' => $id]);
        #$item = $this->docs->getInventoryItem($id);
        if (!$item) {
            throw $this->createNotFoundException('Item not found');
        }
        if ($request->getMethod() === 'POST' && $request->request->get['action'] === 'delete') {
            $this->files->deleteItemFile($item, $filename);
            return new JsonResponse(['success' => 1]);
        } else {
            $path = $this->files->getFilePath($item, $filename);
            if (file_exists($path)) {
                return new BinaryFileResponse($path, Response::HTTP_OK, ['Cache-Control' => 'max-age=14400']);
            } else {
                throw $this->createNotFoundException('File not found');
            }
        }
    }

    protected function saveTags($category, $tagNames)
    {
        foreach ($tagNames as $tagName) {
            $item = $this->tagRepo->findOneByName($category, $tagName);
            if (!$item) {
                $newTag = new Tag();
                $newTag->setCategory($category);
                $newTag->setName($tagName);
                try {
                    $this->dm->persist($newTag);
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
    }
}
