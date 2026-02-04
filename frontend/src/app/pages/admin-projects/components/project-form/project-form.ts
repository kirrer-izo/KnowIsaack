import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';

@Component({
  selector: 'app-project-form',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './project-form.html',
  styleUrl: './project-form.scss',
})
export class ProjectForm {
  @Input() project: any = null;
  @Input() isSubmitting = false;
  @Output() save = new EventEmitter<any>();

  form: FormGroup;

    constructor(private fb: FormBuilder) {
    this.form = this.fb.group({
      id: [null],
      name: ['', Validators.required],
      description: ['',],
      goal: [''],
      source_code: ['', Validators.required],
      live_demo: ['']
    });
  }

  ngOnChanges() {
    if (this.project) {
      this.form.patchValue(this.project);
    } else {
      this.form.reset();
    }
  }

  onSubmit() {
    if (this.form.valid) {
      this.save.emit(this.form.value);
    }
  }


}
