import {
  Column,
  CreateDateColumn,
  Entity,
  Index,
  ManyToMany,
  PrimaryGeneratedColumn,
  UpdateDateColumn,
} from 'typeorm';
import { Article } from '../../articles/entities/article.entity';

@Entity('categories')
export class Category {
  @PrimaryGeneratedColumn()
  id!: number;

  @Index({ unique: true })
  @Column({ length: 120 })
  name!: string;

  @Index({ unique: true })
  @Column({ length: 150 })
  slug!: string;

  @Column({ type: 'text', nullable: true })
  description!: string | null;

  @Column({ type: 'varchar', name: 'meta_title', length: 255, nullable: true })
  metaTitle!: string | null;

  @Column({ type: 'varchar', name: 'meta_description', length: 300, nullable: true })
  metaDescription!: string | null;

  @ManyToMany(() => Article, (article) => article.categories)
  articles!: Article[];

  @CreateDateColumn({ name: 'created_at' })
  createdAt!: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt!: Date;
}
