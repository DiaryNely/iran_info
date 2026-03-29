import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CategoriesService } from '../../categories/services/categories.service';
import { UsersService } from '../../users/services/users.service';
import { CreateArticleDto } from '../dto/create-article.dto';
import { UpdateArticleDto } from '../dto/update-article.dto';
import { Article } from '../entities/article.entity';

@Injectable()
export class ArticlesService {
  constructor(
    @InjectRepository(Article)
    private readonly articlesRepository: Repository<Article>,
    private readonly categoriesService: CategoriesService,
    private readonly usersService: UsersService,
  ) {}

  findAll() {
    return this.articlesRepository.find({
      relations: {
        author: true,
        categories: true,
      },
      order: { createdAt: 'DESC' },
    });
  }

  async findOneBySlug(slug: string) {
    const article = await this.articlesRepository.findOne({
      where: { slug },
      relations: { author: true, categories: true },
    });

    if (!article) {
      throw new NotFoundException('Article not found');
    }

    return article;
  }

  async create(dto: CreateArticleDto, userId: number) {
    const existingSlug = await this.articlesRepository.findOne({ where: { slug: dto.slug } });
    if (existingSlug) {
      throw new BadRequestException('Article slug already exists');
    }

    const user = await this.usersService.findById(userId);
    if (!user) {
      throw new NotFoundException('Author not found');
    }

    const categories = await this.categoriesService.findByIds(dto.categoryIds);

    const article = this.articlesRepository.create({
      title: dto.title,
      content: dto.content,
      image: dto.image ?? null,
      slug: dto.slug,
      metaTitle: dto.metaTitle ?? null,
      metaDescription: dto.metaDescription ?? null,
      author: user,
      categories,
    });

    return this.articlesRepository.save(article);
  }

  async update(id: number, dto: UpdateArticleDto) {
    const article = await this.articlesRepository.findOne({
      where: { id },
      relations: { categories: true },
    });

    if (!article) {
      throw new NotFoundException('Article not found');
    }

    if (dto.slug && dto.slug !== article.slug) {
      const existingSlug = await this.articlesRepository.findOne({ where: { slug: dto.slug } });
      if (existingSlug) {
        throw new BadRequestException('Article slug already exists');
      }
    }

    if (dto.categoryIds) {
      article.categories = await this.categoriesService.findByIds(dto.categoryIds);
    }

    Object.assign(article, {
      ...dto,
      image: dto.image ?? article.image,
      metaTitle: dto.metaTitle ?? article.metaTitle,
      metaDescription: dto.metaDescription ?? article.metaDescription,
    });

    return this.articlesRepository.save(article);
  }

  async remove(id: number) {
    const article = await this.articlesRepository.findOne({ where: { id } });
    if (!article) {
      throw new NotFoundException('Article not found');
    }

    await this.articlesRepository.remove(article);
    return { message: 'Article deleted' };
  }
}
