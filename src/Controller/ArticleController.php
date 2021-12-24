<?php

namespace App\Controller;


use App\Entity\Article;
use App\Form\ArticleType;
use App\Entity\Commentary;
use App\Form\EditArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ArticleController extends AbstractController
{

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/admin/article", name="create_article")
     * @param Request $request
     * @return Response
     */
    public function createArticle(Request $request, SluggerInterface $slugger): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $article = $form->getData();

            $article->setUser($this->getUser());
           
            # Association de l'article au user : setOwner()
            //

            # Association de l'article à la category : setOwner()
            //

            $article->setCreatedAt(new \DateTime());

            # Coder ici la logique pour uploader la photo
            //On récupere le fichier du formulaire grâce a getData(). Cela nous retourne un objet de type uploadedFile
            
            $file = $form->get('picture')->getData();
            //Condition qui verifie si un fichier est présent dans le formulaire 
            if ($file) {
                //dd($file);

                //Générer une contrainte d'upload. On déclare un array avec deux valeurs de type string qui sont les MimeType autorisés.
                //Vous retrouvez tous les MimeTyppe existant sur internet 
                // $allowedMimeType = ['image/jpeg', 'image/png'];
                // La fonction native in_array() permet de comparer deux valeur (2arguments attendus)
                // if (in_array($file->getMimeType(), $allowedMimeType)) {

                    // Nous allons construire le nouveau du fichier

                    //On stocke dans une variable $originalFilename le nom du fichier 
                    //On utilise encore une fonction native pathinfo()
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);


                    # Récupération de l'extension

                    //Récupération de l'extension pour pouvoir reconstruire le quelques lignes aprés 
                    //On utilise la concatenation pour ajouter un point '.'
                    $extension = $file->guessExtension();

                    # Assainissement du nom grâce au slugger fourni par Symfony pour la construction du nouveau nom
                    // $safeFilename = $slugger->slug($article->getTitle());
                    $safeFilename = $slugger->slug($originalFilename);

                    # Construction du nouveau nom
                    //uniqid() est une fonction native qui permet de générer un ID unique.
                    $newFilename = $safeFilename . '_' . uniqid() . $extension;
                    /**
                     * On utilise un try{} catch(){} lorsqu'on appelle une méthode qui lance une errur.
                     */
                    try{
                        /**
                         * Ici on appelle la méthode move() de UploadedFile pour pouvoir déplacer le fichier dans son dossier de destination .
                        *le dossier de destination a été parametré dans services.yaml
                        * /!\ATTENTION : 
                               * La méthode move() lance une erreur de type FileException
                               * on attrape cette erreur dans le catch(FileException !exception)
                         */
                        
                        $file->move($this->getParameter('uploads_dir'), $newFilename);

                        // On set la nouvelle valeur (nom du fichier) de la propriété picture de notre objet Article 
                        $article->setPicture($newFilename);
                    }catch (FileException $exception) {
                        //code a éxécuter si une erreur est attrapée
                        

                     }
                // }
                // //Si ce n'est pas le bon type de fichier uploadé, alors on affiche un message et on redirige 
                // else {
                //     $this->addFlash('warning', 'Les types de de fichier autorisés sont : .jpeg / .png' );
                //     return $this->redirectToRoute('create_article');
                // }
            }





            $this->entityManager->persist($article);
            $this->entityManager->flush();

            $this->addFlash('success', 'Article ajouté!');

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('dashboard/form_article.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/modifier/article/{id}", name="edit_article")
     * @param Article $article
     * @param Request $request
     * @return Response
     */
    public function editArticle(Article $article, Request $request): Response
    {
        # Supprimer le edit form et utiliser ArticleType (configurer les options) : pas besoin de dupliquer un form
        $form = $this->createForm(EditArticleType::class, $article)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            # Créer une nouvelle propriété dans l'entité : setUpdatedAt()

            $this->entityManager->persist($article);
            $this->entityManager->flush();
        }

        return $this->render('article/edit_article.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/voir/article/{id}", name="show_article")
     * @param Article $singleArticle
     * @return Response
     */
    public function showArticle(Article $singleArticle): Response
    {
        $article = $this->entityManager->getRepository(Article::class)->find($singleArticle->getId());
        $commentaries = $this->entityManager->getRepository(Commentary::class)->findby(['article' => $singleArticle->getId()]);

        return $this->render('article/show_article.html.twig', [
            'article' => $article,
            'commentaries' => $commentaries,
        ]);
    }

    /**
     * @Route("/admin/supprimer/article/{id}", name="delete_article")
     * @param Article $article
     * @return Response
     */
    public function deleteArticle(Article $article): Response
    {
        $this->entityManager->remove($article);
        $this->entityManager->flush();

        $this->addFlash('success', 'Article supprimé !');

        return $this->redirectToRoute('dashboard');
    }
}
