import { CommonModule } from '@angular/common';
import { ChangeDetectorRef, Component, inject, OnInit } from '@angular/core';
import { ActivatedRoute, RouterModule } from '@angular/router';
import { ProjectService } from '../../data-access/project.service';
import { Project } from '../../data-access/project.model';

@Component({
  selector: 'app-project-details',
  imports: [CommonModule, RouterModule],
  templateUrl: './project-details.html',
  styleUrl: './project-details.scss',
})
export class ProjectDetails implements OnInit {
  private route = inject(ActivatedRoute);
  private projectService = inject(ProjectService);

  project: Project | null = null;
  isLoading = true;

 ngOnInit() {
  this.route.paramMap.subscribe(params => {
    const id = params.get('id');
    if (id) {
      this.loadProject(+id);
    }
  })
 }

 private loadProject(id: number) {
  this.isLoading = true;

  this.projectService.getProject(id).subscribe({
    next: project => {
      this.project = project;
      this.isLoading = false;
    },
    error: () => {
      this.project = null;
      this.isLoading = false;
    }
  })
 }

}
