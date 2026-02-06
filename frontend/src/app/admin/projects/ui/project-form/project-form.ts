import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, OnChanges, Output, SimpleChanges } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';

import { Project } from '../../data-access/project.model';
import { TechStack } from '../../data-access/tech-stack.model';

@Component({
  selector: 'app-project-form',
  standalone: true,
  imports: [ReactiveFormsModule, CommonModule],
  templateUrl: './project-form.html',
  styleUrl: './project-form.scss',
})
export class ProjectForm implements OnChanges{
  @Input() project: Project | null = null;
  @Input() techStacks: TechStack[] = [];
  @Input() isSubmitting = false;

  @Output() save = new EventEmitter<Project>();

    form: FormGroup;

  constructor(private fb: FormBuilder) {
    this.form = this.fb.nonNullable.group({
        id: [null as number | null],
        name: ['', Validators.required],
        description: [''],
        goal: [''],
        source_code: ['', Validators.required],
        live_demo: [''],
        tech_stack_ids:[[] as number[]],
      });

  }


  get isEditMode(): boolean {
    return !!this.project?.id;
  }


  ngOnChanges(changes: SimpleChanges) {
    if (changes['project']) {
      if (this.project) {
        this.form.patchValue({
          ...this.project,
          tech_stack_ids: this.project.tech_stack_ids ?? [],
        });
      } else {
        this.form.reset({
          tech_stack_ids: [],
        })
      }
    }
  }

  isTechStackSelected(techId: number): boolean {
    const selectedIds = this.form.get('tech_stack_ids')?.value || [];
    return selectedIds.includes(techId);
  }

  onTechStackChange(techId: number, event: Event): void {
    const checkbox = event.target as HTMLInputElement;
    const currentIds = this.form.get('tech_stack_ids')?.value || [];
    
    if (checkbox.checked) {
      this.form.patchValue({
        tech_stack_ids: [...currentIds, techId]
      });
    } else {
      this.form.patchValue({
        tech_stack_ids: currentIds.filter((id: number) => id !== techId)
      });
    }
  }

  onSubmit() {
    if (this.form.invalid) return;
    
    this.save.emit(this.form.getRawValue())
  }


}
