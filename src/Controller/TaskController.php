<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tasks', name: 'api_task_')]
class TaskController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || !isset($data['projectId'])) {
            return new JsonResponse([
                'code' => 400,
                'error' => 'Task Name and Project ID are required.',
            ], 200);
        }

        $project = $em->getRepository(Project::class)->find($data['projectId']);
        if (!$project) {
            return new JsonResponse([
                'code' => 404,
                'error' => 'Project not found.',
            ], 200);
        }

        $task = new Task();
        $task->setName($data['name']);
        $task->setProject($project);

        $em->persist($task);
        $em->flush();

        return new JsonResponse($task->getData(), 200);
    }

    #[Route('/{id?}', name: 'read', methods: ['GET'])]
    public function read(?string $id, EntityManagerInterface $em): JsonResponse
    {
        $output = [];

        if($id) {
            if (!Uuid::isValid($id)) {
                return new JsonResponse([
                    'code' => 404,
                    'error' => 'Task ID is not valid.',
                ], 200);
            }

            $task = $em->getRepository(Task::class)->findActiveTask($id);

            if (!$task) {
                return new JsonResponse([
                    'code' => 404,
                    'error' => 'Task not found or has been deleted.',
                ], 200);
            }

            $output = $task->getData();

            $output['tasks'] = $task->getTasksData();
        } else {
            $tasks = $em->getRepository(Task::class)->findActiveTasks();

            foreach ($tasks as $task) {
                $output[] = $task->getData();
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
                'error' => 'Task ID is not valid.',
            ], 200);
        }

        $task = $em->getRepository(Task::class)->findActiveTask($id);

        if (!$task) {
            return new JsonResponse([
                'code' => 404,
                'error' => 'Task not found or has been deleted.',
            ], 200);
        }

        $data = json_decode($request->getContent(), true);

        $task->setName($data['name'] ?? $task->getName());

        $em->flush();

        return new JsonResponse($task->getData(), 200);
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

        $task = $em->getRepository(Task::class)->findActiveTask($id);

        if (!$task) {
            return new JsonResponse([
                'code' => 404,
                'error' => 'Task not found.',
            ], 200);
        }

        $task->setDeletedAt(new \DateTimeImmutable());

        $em->flush();

        return new JsonResponse([
            'message' => 'Task deleted successfully.',
        ], 200);
    }
}
