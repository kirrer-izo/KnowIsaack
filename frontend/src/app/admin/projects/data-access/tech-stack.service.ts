import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environment';
import { TechStack } from './tech-stack.model';

@Injectable({
  providedIn: 'root',
})
export class TechStackService {
  private http = inject(HttpClient);
  private apiUrl = `${environment.apiUrl}/techstacks`;

  getTechStacks(): Observable<TechStack[]> {
    return this.http.get<TechStack[]>(this.apiUrl);
  }

  getTechStack(id: number): Observable<TechStack> {
    return this.http.get<TechStack>(`${this.apiUrl}/${id}`);
  }

  createTechStack(techStack: Partial<TechStack>): Observable<TechStack> {
    return this.http.post<TechStack>(this.apiUrl, techStack);
  }

  updateTechStack(id: number, techStack: Partial<TechStack>): Observable<TechStack> {
    return this.http.put<TechStack>(`${this.apiUrl}/${id}`, techStack);
  }

  deleteTechStack(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }
}
