<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use Tests\Integration\IntegrationTestCase;
use App\Infrastructure\Database\ProjectRepository;
use PHPUnit\Framework\Attributes\Test;

class ProjectRepositoryTest extends IntegrationTestCase
{
    private ProjectRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProjectRepository($this->pdo);
    }

    #[Test]
    public function it_can_create_and_retrieve_a_project_with_json_tech_stack(): void
    {
        $data = [
            'title' => 'KnowIsaack',
            'description' => 'My personal portfolio',
            'tech_stack' => ['PHP', 'PostgreSQL', 'Tailwind'],
            'github_url' => 'https://github.com/isaack/knowisaack',
            'detail_url' => '/projects/knowisaack',
            'featured' => true
        ];

        // 1. Create the project
        $this->repository->createProject($data);

        // 2. Retrieve it
        $projects = $this->repository->getAllProjects();
        $project = $projects[0];

        $this->assertCount(1, $projects);
        $this->assertEquals('KnowIsaack', $project['title']);
        
        // 3. CRITICAL: Check that tech_stack was correctly decoded from JSON to an array
        $this->assertIsArray($project['tech_stack']);
        $this->assertContains('PostgreSQL', $project['tech_stack']);
        $this->assertTrue($project['featured']);
    }

    #[Test]
    public function it_returns_null_for_non_existent_project_id(): void
    {
        $result = $this->repository->getProjectById(999);
        $this->assertNull($result);
    }

    #[Test]
    public function it_can_update_an_existing_project(): void
    {
        // 1. Setup
        $data = [
            'title' => 'Old Title',
            'description' => 'Old Desc',
            'tech_stack' => ['HTML'],
            'github_url' => '',
            'detail_url' => '',
            'featured' => false
        ];
        $this->repository->createProject($data);
        $project = $this->repository->getAllProjects()[0];
        $projectId = (int)$project['id'];

        // 2. Action
        $data['title'] = 'New Title';
        $data['featured'] = true;
        $success = $this->repository->updateProject($projectId, $data);

        // 3. Assert
        $this->assertTrue($success);
        $updated = $this->repository->getProjectById($projectId);
        $this->assertEquals('New Title', $updated['title']);
        $this->assertTrue($updated['featured']);
    }

    #[Test]
    public function it_calculates_dashboard_stats_correctly(): void
    {
        // Setup: Create 1 featured and 1 non-featured project
        $this->repository->createProject(['title' => 'P1', 'description' => '', 'tech_stack' => [], 'github_url' => '', 'detail_url' => '', 'featured' => true]);
        $this->repository->createProject(['title' => 'P2', 'description' => '', 'tech_stack' => [], 'github_url' => '', 'detail_url' => '', 'featured' => false]);

        $this->assertEquals(2, $this->repository->countAll());
        $this->assertEquals(1, $this->repository->countFeatured());
    }

    #[Test]
    public function it_handles_pagination_and_case_insensitive_search(): void
    {
        // Setup: Create two projects with specific titles
        $this->repository->createProject(['title' => 'Laravel Project', 'description' => 'Backend', 'tech_stack' => [], 'github_url' => '', 'detail_url' => '', 'featured' => false]);
        $this->repository->createProject(['title' => 'React Portfolio', 'description' => 'Frontend', 'tech_stack' => [], 'github_url' => '', 'detail_url' => '', 'featured' => false]);

        // Test search (lower case 'laravel' should match 'Laravel Project' due to ILIKE)
        $result = $this->repository->getPaginatedProjects(1, 10, 'laravel');

        $this->assertEquals(1, $result['total']);
        $this->assertEquals('Laravel Project', $result['projects'][0]['title']);
    }

#[Test]
public function it_retrieves_recent_projects_in_correct_order(): void
{
    // 1. Create two projects
    $this->repository->createProject(['title' => 'Oldest', 'description' => '', 'tech_stack' => [], 'github_url' => '', 'detail_url' => '', 'featured' => false]);
    $this->repository->createProject(['title' => 'Newest', 'description' => '', 'tech_stack' => [], 'github_url' => '', 'detail_url' => '', 'featured' => false]);

    // 2. RAW SQL FIX: Manually force one to be 10 minutes in the future
    // This breaks the "identical timestamp" tie-breaker.
    $this->pdo->exec("UPDATE projects SET created_at = created_at + INTERVAL '10 minutes' WHERE title = 'Newest'");

    $recent = $this->repository->getRecent(1);

    $this->assertCount(1, $recent);
    $this->assertEquals('Newest', $recent[0]['title']);
}
}