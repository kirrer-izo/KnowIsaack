import { CommonModule } from '@angular/common';
import { ChangeDetectorRef, Component, inject, OnInit } from '@angular/core';
import { ActivatedRoute, RouterModule } from '@angular/router';
import { ProjectService } from '../admin-projects/services/project';

@Component({
  selector: 'app-project-details',
  imports: [CommonModule, RouterModule],
  templateUrl: './project-details.component.html',
  styleUrl: './project-details.component.scss',
})
export class ProjectDetailsComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private projectService = inject(ProjectService);
  private cd = inject(ChangeDetectorRef);

  project: any = null;
  isLoading = true;

  ngOnInit() {
    const id = this.route.snapshot.paramMap.get('id');

    if (id) {
      this.projectService.getProject(+id).subscribe({
        next: (res) => {
          this.project = res;
          this.isLoading = false;
          this.cd.detectChanges();
        },
        error: (err) => {
          console.error('Project not found', err);
          this.isLoading = false;
          this.cd.detectChanges();
        }
      })
    }
  }

}
