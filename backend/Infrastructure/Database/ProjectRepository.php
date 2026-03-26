<?php

namespace App\Infrastructure\Database;

use PDO;

class ProjectRepository {
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllProjects(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM projects");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(function ($project) {
            $project['tech_stack'] = json_decode($project['tech_stack'], true);
            return $project;
        }, $projects);
    }

    public function getProjectById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM projects WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$project) {
            return null;
        }
        return array_merge($project, ['tech_stack' => json_decode($project['tech_stack'], true)]);
    }

    public function createProject(array $data): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO projects (title, description, tech_stack, github_url, detail_url, featured) VALUES (:title, :description, :tech_stack, :github_url, :detail_url, :featured)");
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'],
            'tech_stack' => json_encode($data['tech_stack']),
            'github_url' => $data['github_url'],
            'detail_url' => $data['detail_url'],
            'featured' => $data['featured'] ? 1 : 0
        ]);
    }

    public function updateProject(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("UPDATE projects SET title = :title, description = :description, tech_stack = :tech_stack, github_url = :github_url, detail_url = :detail_url, featured = :featured WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'],
            'tech_stack' => json_encode($data['tech_stack']),
            'github_url' => $data['github_url'],
            'detail_url' => $data['detail_url'],
            'featured' => $data['featured'] ? 1 : 0
        ]);
    }

    public function deleteProject(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM projects WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    // Get total number of registered projects
    public function countAll(): int 
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM projects");
        return (int) $stmt->fetchColumn();
    }

    // Get number of projects with featured projects
    public function countFeatured(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM projects WHERE featured = true");
        return (int) $stmt->fetchColumn();
    }
}