import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { Project } from '../../data-access/project.model';

@Component({
  selector: 'app-project-list',
  standalone:true,
  imports: [CommonModule],
  templateUrl: './project-list.html',
  styleUrl: './project-list.scss',
})
export class ProjectList {
  @Input() projects: Project[] = [];

  @Output() add = new EventEmitter<void>();
  @Output() edit = new EventEmitter<Project>();
  @Output() delete = new EventEmitter<number>();
  @Output() view = new EventEmitter<number>();
  
  get hasProjects(): boolean {
    return !!this.projects && this.projects.length > 0;
  }
}
