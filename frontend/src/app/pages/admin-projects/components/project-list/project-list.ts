import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';

@Component({
  selector: 'app-project-list',
  standalone:true,
  imports: [CommonModule],
  templateUrl: './project-list.html',
  styleUrl: './project-list.scss',
})
export class ProjectList {
  @Input() projects: any[] = [];
  @Output() edit = new EventEmitter<any>();
  @Output() delete = new EventEmitter<number>();
  @Output() view = new EventEmitter<number>();
  
}
