<?php

namespace App\Controller;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/projects', name: 'api_project_')]
class ProjectController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        $data = json_decode($request->getContent(), true);

        $project = new Project();
        $project->setTitle($data['title'] ?? null);
        $project->setDescription($data['description'] ?? null);
        $project->setStatus($data['status'] ?? null);
        $project->setDuration($data['duration'] ?? null);
        $project->setClient($data['client'] ?? null);
        $project->setCompany($data['company'] ?? null);

        $errors = $validator->validate($project);

        if (count($errors) > 0) {
            $errorMessages = [];
            
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }

            return new JsonResponse([
                'code' => 400,
                'error' => implode('; ', $errorMessages)
            ], 200);
        }

        $em->persist($project);
        $em->flush();

        return new JsonResponse($project->getData(), 200);
    }

    #[Route('/{id?}', name: 'read', methods: ['GET'])]
    public function read(?string $id, EntityManagerInterface $em): JsonResponse
    {
        $output = [];

        if($id) {
            if (!Uuid::isValid($id)) {
                return new JsonResponse([
                    'code' => 404,
                    'error' => 'Project ID is not valid.',
                ], 200);
            }

            $project = $em->getRepository(Project::class)->findActiveProject($id);

            if (!$project) {
                return new JsonResponse([
                    'code' => 404,
                    'error' => 'Project not found or has been deleted.',
                ], 200);
            }

            $output = $project->getData();

            $output['tasks'] = $project->getTasksData();
        } else {
            $projects = $em->getRepository(Project::class)->findActiveProjects();

            foreach ($projects as $project) {
                $output[] = $project->getData();
            }
        }

        return new JsonResponse($output, 200);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse([
                'code' => 404,
                'error' => 'Project ID is not valid.',
            ], 200);
        }

        $project = $em->getRepository(Project::class)->findActiveProject($id);

        if (!$project) {
            return new JsonResponse([
                'code' => 404,
                'error' => 'Project not found or has been deleted.',
            ], 200);
        }

        $data = json_decode($request->getContent(), true);

        $project->setTitle($data['title'] ?? $project->getTitle());
        $project->setDescription($data['description'] ?? $project->getDescription());
        $project->setStatus($data['status'] ?? $project->getStatus());
        $project->setDuration($data['duration'] ?? $project->getDuration());
        $project->setClient($data['client'] ?? $project->getClient());
        $project->setCompany($data['company'] ?? $project->getCompany());

        $errors = $validator->validate($project);

        if (count($errors) > 0) {
            $errorMessages = [];
            
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }

            return new JsonResponse([
                'code' => 400,
                'error' => implode('; ', $errorMessages)
            ], 200);
        }

        $em->flush();

        return new JsonResponse($project->getData(), 200);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id, EntityManagerInterface $em)
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse([
                'code' => 404,
                'error' => 'Project ID is not valid.',
            ], 200);
        }

        $project = $em->getRepository(Project::class)->findActiveProject($id);

        if (!$project) {
            return new JsonResponse([
                'code' => 404,
                'error' => 'Project not found.',
            ], 200);
        }

        $project->setDeletedAt(new \DateTimeImmutable());

        $em->flush();

        return new JsonResponse([
            'message' => 'Project deleted successfully.',
        ], 200);
    }
}
