import { CommonModule } from '@angular/common';
import { ChangeDetectorRef, Component, inject, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { ProjectService } from '../../services/project.service';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-admin-projects',
  standalone: true,
  imports: [ReactiveFormsModule, CommonModule, RouterModule],
  templateUrl: './admin-projects.component.html',
  styleUrl: './admin-projects.component.scss',
})
export class AdminProjectsComponent implements OnInit {
  //services
  private projectService = inject(ProjectService);
  private fb = inject(FormBuilder);
  private cd = inject(ChangeDetectorRef);

  //state
  projects: any[] = [];
  projectForm: FormGroup;
  isSubmitting = false;

  constructor() {
    this.projectForm = this.fb.group({
      name: ['', Validators.required],
      description: ['',],
      goal: [''],
      source_code: ['', Validators.required],
      live_demo: ['']
    });
  }

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

  onSubmit() {
    if (this.projectForm.valid) {
      this.isSubmitting = true;

      this.projectService.createProject(this.projectForm.value).subscribe({
        next: (res) => {
          alert('Project Created!');
          this.projectForm.reset();
          this.isSubmitting = false;
          this.loadProjects();
        },
        error: (err) => {
          console.error(err);
          alert('Error creating project');
          this.isSubmitting = false;
        }
      });
    }
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
