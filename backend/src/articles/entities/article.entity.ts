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

  @Column({ type: 'text', nullable: true })
  image!: string | null;

  @Column({ type: 'varchar', name: 'meta_title', length: 255, nullable: true })
  metaTitle!: string | null;

  @Column({ type: 'varchar', name: 'meta_description', length: 300, nullable: true })
  metaDescription!: string | null;

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
