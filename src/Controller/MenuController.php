<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Menu;
use App\Form\MenuType;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class MenuController extends AbstractController
{
    /**
     * @Route("admin/menus", name="menus_index")
     * 
     * @return Response
     */
    public function index(MenuRepository $repo, PaginatorInterface $paginator, Request $request): Response
    {
        $menus = $repo->findAll();

        $pagination = $paginator->paginate(
            $menus,
            $request->query->getInt('page', 1), /*page number*/
            6 /*limit per page*/
        );

        return $this->render('admin/menu/index.html.twig', [
            'menus' => $menus,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Permet d'afficher le formualire d'ajout
     * 
     * @Route("/admin/menus/new", name="menu_create")
     * 
     * @return Response
     */
    public function create(Request $request): Response
    {
        $menu = new Menu();

        $form = $this->createForm(MenuType::class, $menu, array('attr'=>array('novalidate'=>'novalidate')));
        
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $manager = $this->getDoctrine()->getManager();
            $file1 = $menu->getMatinImg();
            $fileName1 = md5(uniqid()) . '.' . $file1->guessExtension();
            $file2 = $menu->getDejeunerImg();
            $fileName2 = md5(uniqid()) . '.' . $file2->guessExtension();
            $file3 = $menu->getDinnerImg();
            $fileName3 = md5(uniqid()) . '.' . $file3->guessExtension();

            try {
                $file1->move(
                    $this->getParameter('menu_images_directory'),
                    $fileName1
                );
                $file2->move(
                    $this->getParameter('menu_images_directory'),
                    $fileName2
                );
                $file3->move(
                    $this->getParameter('menu_images_directory'),
                    $fileName3
                );
            } catch (FileException $e) {
                // ... handle exception if something happens during file upload
            }

            $manager->persist($menu);
            $menu->setMatinImg($fileName1);
            $menu->setDejeunerImg($fileName2);
            $menu->setDinnerImg($fileName3);
            $manager->flush();

            $this->addFlash('success', "L'ajout du <strong>{$menu->getDescription()}<strong> a ??t?? bien enregistr??e!");

            return $this->redirectToRoute('menus_index');
        }

        return $this->render('admin/menu/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Permet de modifier le formualire d'??dition
     * 
     * @Route("/admin/menus/{id}/edit", name="menu_edit")
     * 
     * @return Response
     */
    public function edit(Request $request, Menu $menu): Response
    {
        $form = $this->createForm(MenuType::class, $menu, array('attr'=>array('novalidate'=>'novalidate')));
        
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $manager = $this->getDoctrine()->getManager();
            $file1 = $menu->getMatinImg();
            $fileName1 = md5(uniqid()) . '.' . $file1->guessExtension();
            $file2 = $menu->getDejeunerImg();
            $fileName2 = md5(uniqid()) . '.' . $file2->guessExtension();
            $file3 = $menu->getDinnerImg();
            $fileName3 = md5(uniqid()) . '.' . $file3->guessExtension();

            try {
                $file1->move(
                    $this->getParameter('menu_images_directory'),
                    $fileName1
                );
                $file2->move(
                    $this->getParameter('menu_images_directory'),
                    $fileName2
                );
                $file3->move(
                    $this->getParameter('menu_images_directory'),
                    $fileName3
                );
            } catch (FileException $e) {
                // ... handle exception if something happens during file upload
            }

            $manager->persist($menu);
            $menu->setMatinImg($fileName1);
            $menu->setDejeunerImg($fileName2);
            $menu->setDinnerImg($fileName3);
            $manager->flush();


            $this->addFlash('success', "Le <strong>{$menu->getDescription()}<strong> a ??t?? bien modifi??e!");

            return $this->redirectToRoute('menus_index');
        }

        return $this->render('admin/menu/edit.html.twig', [
            'form' => $form->createView(),
            'menu' => $menu,
        ]);
    }

    /**
     * Permet de supprimer un menu
     * 
     * @Route("/admin/menus/{id}/delete", name="menu_delete")
     * 
     * @param Menu $menu
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function delete(Menu $menu, EntityManagerInterface $manager){
        $manager->remove($menu);
        $manager->flush();

        $this->addFlash('success', "Le <strong>{$menu->getDescription()}<strong> a ??t?? bien supprim??e!");

        return $this->redirectToRoute('menus_index');
    }

    /**
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/admin/menus/search", name="searchAdmin")
     * 
     */
    public function searchAdmin(MenuRepository $repository, PaginatorInterface $paginator, Request $request)
    {
        $requestString = $request->get('searchValue');
        $menu = $repository->findAllMenubyDescription($requestString);
        
        $pagination = $paginator->paginate(
            $menu,
            $request->query->getInt('page', 1), /*page number*/
            6 /*limit per page*/
        );

        return $this->render('admin/menu/_search.html.twig', [
            'menu' => $menu,
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/admin/menus/statistique", name="menu_stat")
     */
    public function chartAction()
    { 
        $ob = new Highchart();
        $ob->chart->renderTo('linechart');
        $ob->title->text('Statistiques des Menus');
        $ob->chart->type('column');
        $ob->xAxis->title(array('text'  => "L'identitfiant des menus"));
        $ob->yAxis->title(array('text'  => "Nombre de Calories"));

        $menus = $this->getDoctrine()
            ->getRepository(Menu::class)
            ->findAll();

        $data =array();

        foreach ($menus as $values)
        {
            $a =array($values->getDescription(),intval($values->getTotalCalories()));
            array_push($data,$a);
        }

        $ob->series(array(array('name' => 'Courbe Calorique', 'data' => $data)));
       
        return $this->render('admin/menu/stat.html.twig', array(
            'chart' => $ob
        ));
    }

    /**
     * @Route("/admin/menus/pdf", name="pdf")
     */
    public function pdf()
    {
        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);

        $menus = $this->getDoctrine()->getManager()->getRepository(Menu::class)->findAll();

        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('admin/menu/listep.html.twig', [
            'menus' => $menus,
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream("mypdf.pdf", [
            "Attachment" => true
        ]);
    }
}
