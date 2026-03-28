import { Column, Entity, PrimaryGeneratedColumn } from 'typeorm';

@Entity('articles')
export class Article {
  @PrimaryGeneratedColumn()
  id!: number;

  @Column({ unique: true })
  slug!: string;

  @Column()
  title!: string;

  @Column({ type: 'text' })
  excerpt!: string;

  @Column({ type: 'text' })
  content!: string;

  @Column({ default: true })
  published!: boolean;
}
