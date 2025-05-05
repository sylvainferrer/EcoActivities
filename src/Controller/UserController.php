<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $hasher)
    {
        $this->em = $em;
        $this->hasher = $hasher;
    }

    // ✅ Lister tous les utilisateurs
    #[Route('/', name: 'user_index', methods: ['GET'])]
    public function index(UserRepository $repository): JsonResponse
    {
        $users = $repository->findAll();

        $data = array_map(function (User $user): array {
            return [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ];
        }, $users);

        return $this->json($data);
    }

    // ✅ Créer un nouvel utilisateur
    #[Route('/create', name: 'user_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['nom'], $data['prenom'])) {
            return $this->json(['message' => 'Données incomplètes'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);

       
        $hashedPassword = $this->hasher->hashPassword($user, $data['password']);

       
        $user->setPassword($hashedPassword);

        $roles = $data['roles'] ?? ['ROLE_USER'];
        $user->setRoles($roles);

       
        $this->em->persist($user);
        $this->em->flush();

        return $this->json(['message' => 'Utilisateur créé'], Response::HTTP_CREATED);
    }

    // ✅ Modifier un utilisateur
    #[Route('/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(int $id, Request $request, UserRepository $repository): JsonResponse
    {
        $user = $repository->find($id);
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) $user->setEmail($data['email']);
        if (isset($data['nom'])) $user->setNom($data['nom']);
        if (isset($data['prenom'])) $user->setPrenom($data['prenom']);
        if (isset($data['roles'])) $user->setRoles($data['roles']);

        if (isset($data['password'])) {
            $hashedPassword = $this->hasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        $this->em->flush();

        return $this->json(['message' => 'Utilisateur mis à jour']);
    }

    // ✅ Supprimer un utilisateur
    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(int $id, UserRepository $repository): JsonResponse
    {
        $user = $repository->find($id);
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($user);
        $this->em->flush();

        return $this->json(['message' => 'Utilisateur supprimé']);
    }
}
