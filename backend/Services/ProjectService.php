<?php

namespace App\Services;

use App\Infrastructure\Database\ProjectRepository;

class ProjectService {
    private $projectRepository;

    public function __construct(ProjectRepository $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    public function getAllProjects(): array
    {
        return $this->projectRepository->getAllProjects();
    }

    public function getProjectById(int $id): ?array
    {
        return $this->projectRepository->getProjectById($id);
    }

    public function createProject(array $data): void
    {
        $this->projectRepository->createProject($data);
    }

    public function updateProject(int $id, array $data): bool
    {
        return $this->projectRepository->updateProject($id, $data);
    }

    public function deleteProject(int $id): bool
    {
        return $this->projectRepository->deleteProject($id);
    }
}