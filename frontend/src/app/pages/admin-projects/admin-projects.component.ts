import { CommonModule } from '@angular/common';
import { ChangeDetectorRef, Component, inject, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { ProjectService } from '../admin-projects/services/project';
import { Router, RouterModule } from '@angular/router';
import { ProjectForm } from './components/project-form/project-form';
import { ProjectList } from './components/project-list/project-list';

@Component({
  selector: 'app-admin-projects',
  standalone: true,
  imports: [ReactiveFormsModule, CommonModule, RouterModule, ProjectForm, ProjectList],
  templateUrl: './admin-projects.component.html',
  styleUrl: './admin-projects.component.scss',
})
export class AdminProjectsComponent implements OnInit {
  //services
  private projectService = inject(ProjectService);
  private router = inject(Router);
  private cd = inject(ChangeDetectorRef);

  //state
  projects: any[] = [];
  selectedProject: any = null;
  isSubmitting = false;


  ngOnInit(): void {
    this.loadProjects();
  }

  loadProjects() {
    this.projectService.getProjects().subscribe({
      next: (res: any) => {
        this.projects = res.projects || res;
        this.cd.detectChanges();
      },
      error: (err) => console.error('Failed to load projects', err)
    })
  }

  onEditProject(project: any) {
    this.selectedProject = project;
  }

  onViewProject(id: number) {
    this.router.navigate(['/projects', id])
  }

  onSaveProject(project: any) {
    this.isSubmitting = true;

    const obs = project.id
      ? this.projectService.editProject(project)
      : this.projectService.createProject(project);

      obs.subscribe({
        next: () => {
          this.selectedProject = null;
          this.isSubmitting = false;
          this.loadProjects();
        },
        error: (err) => {
          console.error(err);
          alert('Failed to save project');
          this.isSubmitting = false;
        }
      });
  }

  deleteProject(id: number) {
    if (confirm('Are you sure you want to delete this project?')) {
      this.projectService.deleteProject(id).subscribe({
        next: () => {
          this.loadProjects();
        },
        error: (err) => alert('Failed to delete')
      });
    }
  }

}
