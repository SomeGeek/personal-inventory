<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Home extends AbstractController
{
    public function index()
    {
        return $this->redirectToRoute('inventory_list');
    }
}
