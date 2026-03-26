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

    // Get paginated projects with optional search and featured filter
    public function getPaginatedProjects(int $page, int $limit, ?string $search = null, ?bool $featured = null): array
    {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT id, title, description, tech_stack, github_url, detail_url, featured, created_at FROM projects WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (title ILIKE :search OR description ILIKE :search)";
            $params['search'] = "%{$search}%";
        }

        if ($featured !== null) {
            $sql .= " AND featured = :featured";
            $params['featured'] = $featured ? 'true' : 'false';
        }

        // Count total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM ({$sql}) AS filtered");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch paginated rows
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode tech_stack JSON for each project
        foreach ($projects as &$project) {
            $project['tech_stack'] = json_decode($project['tech_stack'], true);
        }

        return [
            'total' => $total,
            'projects' => $projects
        ];
    }
    
    // Get all projects for CSV export
    public function getAllProjectsForExport(): array
    {
        $stmt = $this->pdo->query("SELECT id, title, description, tech_stack, github_url, detail_url, featured, created_at FROM projects ORDER BY created_at DESC");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($projects as &$project) {
            $project['tech_stack'] = json_decode($project['tech_stack'], true);
            // Convert tech_stack array to comma-separated string for CSV
            $project['tech_stack'] = implode(', ', $project['tech_stack']);
        }
        return $projects;
    }
}