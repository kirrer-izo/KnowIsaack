import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { ProjectForm } from '../../ui/project-form/project-form';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { ProjectService } from '../../data-access/project.service';
import { Project } from '../../data-access/project.model';
import { TechStack } from '../../data-access/tech-stack.model';
import { TechStackService } from '../../data-access/tech-stack.service';
@Component({
  selector: 'app-project-upsert',
  imports: [CommonModule, ProjectForm, RouterModule],
  templateUrl: './project-upsert.html',
  styleUrl: './project-upsert.scss',
})
export class ProjectUpsert {
  private route = inject(ActivatedRoute);
  private router =  inject(Router);
  private projectService = inject(ProjectService);
  private techStackService = inject(TechStackService);

  project: Project | null = null;
  techStacks: TechStack[] = [];
  isLoading = true;
  isSubmitting = false;
  isEditMode = false;

  ngOnInit() {
    this.route.paramMap.subscribe(params => {
      const id = params.get('id');
      this.loadTechStacks();

      if (id) {
        this.isEditMode = true;
        this.loadProject(+id);
      } else {
        this.isLoading = false;
      }
    })
  }

  private loadProject(id: number) {
    this.projectService.getProject(id).subscribe({
      next: (project) => {
        this.project = project;
        this.isLoading = false;
      },
      error: () => {
        this.isLoading = false;
        this.router.navigate(['/dashboard/projects']);
      },
    });
  }

  private loadTechStacks(): void {
    this.techStackService.getTechStacks().subscribe({
      next: (stacks) => {
        this.techStacks = stacks;
      },
      error: (error) => {
        console.error('Error loading tech stacks:', error);
        this.techStacks = [];
      }
    });
  }

  onSave(project: Project) {
    this.isSubmitting = true;

    const request$ = this.isEditMode
    ? this.projectService.updateProject(project.id!, project)
    : this.projectService.createProject(project);

    request$.subscribe({
      next: () => {
        console.log('Project saved successfully');
        this.isSubmitting = false;
        this.router.navigate(['/dashboard/projects']);
      },
      error: (error) => {
        console.error('Error saving project:', error);
        this.isSubmitting = false;
        alert('Failed to save project. Please make sure you are logged in.');
      }
    });
  }



}
