<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            error_log('DEBUG: Formulaire soumis et valide');
            try {
                // encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                error_log('DEBUG: Mot de passe hashé');
                $entityManager->persist($user);
                $entityManager->flush();
                error_log('DEBUG: Utilisateur sauvé en base');

                // Envoyer un email à l'administrateur pour notification de nouvel utilisateur
                $this->sendAdminNotification($mailer, $user);
                error_log('DEBUG: Email envoyé');

                // Rediriger vers la page de login après l'inscription
                $this->addFlash('success', 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.');
                
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                // Log l'erreur pour debug
                error_log('Erreur lors de l\'inscription: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de la création du compte. Veuillez réessayer.');
            }
        } else {
            if ($form->isSubmitted()) {
                error_log('DEBUG: Formulaire soumis mais invalide');
                foreach ($form->getErrors(true) as $error) {
                    error_log('Erreur formulaire: ' . $error->getMessage());
                }
            } else {
                error_log('DEBUG: Formulaire pas encore soumis');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * Envoie une notification par email à l'administrateur lors de la création d'un nouvel utilisateur
     */
    private function sendAdminNotification(MailerInterface $mailer, User $user): void
    {
        try {
            $email = (new Email())
                ->from($this->getParameter('app.admin_notification.from_email'))
                ->to($this->getParameter('app.admin_notification.to_email'))
                ->subject('Bibliothèque : Nouveau compte utilisateur créé')
                ->html(sprintf(
                    '<h3>Site bibliothèque : Nouveau compte utilisateur créé</h3>
                    <p><strong>Email:</strong> %s</p>
                    <p><strong>Date de création:</strong> %s</p>
                    <p><strong>Rôles:</strong> %s</p>',
                    $user->getEmail(),
                    (new \DateTime())->format('d/m/Y H:i:s'),
                    implode(', ', $user->getRoles())
                ));

            $mailer->send($email);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer l'inscription
            error_log('Erreur lors de l\'envoi de l\'email admin: ' . $e->getMessage());
        }
    }
}