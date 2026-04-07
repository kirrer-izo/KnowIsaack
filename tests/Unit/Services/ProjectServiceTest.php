<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ProjectService;
use App\Infrastructure\Database\ProjectRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;

class ProjectServiceTest extends TestCase
{
    /** @var ProjectRepository|MockObject */
    private $repository;
    private $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProjectRepository::class);
        $this->service = new ProjectService($this->repository);
    }

    #[Test]
    public function it_retrieves_all_projects_from_the_repository(): void
    {
        $projects = [
            ['id' => 1, 'title' => 'KnowIsaack', 'featured' => true],
            ['id' => 2, 'title' => 'CRM System', 'featured' => false]
        ];

        $this->repository->expects($this->once())
            ->method('getAllProjects')
            ->willReturn($projects);

        $result = $this->service->getAllProjects();

        $this->assertCount(2, $result);
        $this->assertEquals('KnowIsaack', $result[0]['title']);
    }

    #[Test]
    public function it_returns_a_single_project_by_id(): void
    {
        $projectId = 1;
        $projectData = ['id' => $projectId, 'title' => 'KnowIsaack'];

        $this->repository->expects($this->once())
            ->method('getProjectById')
            ->with($projectId)
            ->willReturn($projectData);

        $result = $this->service->getProjectById($projectId);

        $this->assertIsArray($result);
        $this->assertEquals($projectId, $result['id']);
    }

    #[Test]
    public function it_returns_null_if_project_is_not_found(): void
    {
        $this->repository->method('getProjectById')->willReturn(null);

        $result = $this->service->getProjectById(999);

        $this->assertNull($result);
    }

    #[Test]
    public function it_delegates_project_creation_to_repository(): void
    {
        $data = ['title' => 'New Project', 'description' => 'A great app'];

        $this->repository->expects($this->once())
            ->method('createProject')
            ->with($data);

        $this->service->createProject($data);
    }

    #[Test]
    public function it_returns_true_on_successful_update(): void
    {
        $projectId = 1;
        $data = ['title' => 'Updated Title'];

        $this->repository->expects($this->once())
            ->method('updateProject')
            ->with($projectId, $data)
            ->willReturn(true);

        $result = $this->service->updateProject($projectId, $data);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_true_on_successful_deletion(): void
    {
        $projectId = 1;

        $this->repository->expects($this->once())
            ->method('deleteProject')
            ->with($projectId)
            ->willReturn(true);

        $result = $this->service->deleteProject($projectId);

        $this->assertTrue($result);
    }
}