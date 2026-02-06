import { ChangeDetectorRef, Component, inject, OnInit } from '@angular/core';
import { ProjectService } from '../../data-access/project.service';
import { Router } from '@angular/router';
import { Project } from '../../data-access/project.model';
import { CommonModule } from '@angular/common';
import { ProjectList } from '../../ui/project-list/project-list';

@Component({
  selector: 'app-project-list-page',
  imports: [CommonModule, ProjectList],
  templateUrl: './project-list-page.html',
  styleUrl: './project-list-page.scss',
})
export class ProjectListPage implements OnInit {
  private projectService = inject(ProjectService);
  private router = inject(Router);

  projects: Project[] = [];
  isLoading = true;

  ngOnInit() {
    this.projectService.getProjects().subscribe({
      next: (projects) => {
        this.projects = projects;
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error loading projects:', err);
        this.isLoading = false;
      }
    })
  }

  onAdd() {
    this.router.navigate(['/dashboard/projects/new']);
  }
  onView(id: number) {
    this.router.navigate(['/dashboard/projects', id]);
  }

  onEdit(project: Project) {
    this.router.navigate(['/dashboard/projects', project.id, 'edit']);
  }

  onDelete(id: number) {
    if (!confirm('Are you sure you want to delete this project?')) return;

    this.projectService.deleteProject(id).subscribe(() => {
      this.projects = this.projects.filter(p => p.id !== id);
    });
  }
}
