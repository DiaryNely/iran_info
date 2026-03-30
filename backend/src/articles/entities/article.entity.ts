import {
  Column,
  CreateDateColumn,
  Entity,
  Index,
  JoinTable,
  ManyToMany,
  ManyToOne,
  PrimaryGeneratedColumn,
  UpdateDateColumn,
} from 'typeorm';
import { Category } from '../../categories/entities/category.entity';
import { User } from '../../users/entities/user.entity';

export enum ArticleStatus {
  PUBLISHED = 'published',
}

export interface GalleryImage {
  path: string;
  alt: string;
}

@Entity('articles')
export class Article {
  @PrimaryGeneratedColumn()
  id!: number;

  @Index({ unique: true })
  @Column({ length: 180 })
  slug!: string;

  @Column({ length: 255 })
  title!: string;

  @Column({ type: 'text' })
  content!: string;

  @Column({ type: 'varchar', name: 'cover_image_path', length: 500, nullable: true })
  coverImagePath!: string | null;

  @Column({ type: 'varchar', name: 'cover_image_alt', length: 160, nullable: true })
  coverImageAlt!: string | null;

  @Column({ type: 'jsonb', name: 'gallery_images', default: () => "'[]'" })
  galleryImages!: GalleryImage[];

  @Column({ type: 'varchar', name: 'meta_title', length: 60, nullable: false })
  metaTitle!: string;

  @Column({ type: 'varchar', name: 'meta_description', length: 160, nullable: false })
  metaDescription!: string;

  @Column({ type: 'varchar', name: 'meta_keywords', length: 255, nullable: false })
  metaKeywords!: string;

  @Column({ type: 'enum', enum: ArticleStatus, default: ArticleStatus.PUBLISHED })
  status!: ArticleStatus;

  @Column({ type: 'boolean', default: false })
  featured!: boolean;

  @ManyToOne(() => User, (user) => user.articles, { nullable: false, onDelete: 'RESTRICT' })
  author!: User;

  @ManyToMany(() => Category, (category) => category.articles)
  @JoinTable({
    name: 'article_category',
    joinColumn: { name: 'article_id', referencedColumnName: 'id' },
    inverseJoinColumn: { name: 'category_id', referencedColumnName: 'id' },
  })
  categories!: Category[];

  @CreateDateColumn({ name: 'created_at' })
  createdAt!: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt!: Date;
}
