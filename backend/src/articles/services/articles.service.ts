import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { unlink } from 'fs/promises';
import { join } from 'path';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CategoriesService } from '../../categories/services/categories.service';
import { UsersService } from '../../users/services/users.service';
import { CreateArticleDto } from '../dto/create-article.dto';
import { UpdateArticleDto } from '../dto/update-article.dto';
import { Article, ArticleStatus, GalleryImage } from '../entities/article.entity';

interface UploadedArticleFiles {
  coverImage?: Express.Multer.File[];
  galleryImages?: Express.Multer.File[];
}

@Injectable()
export class ArticlesService {
  constructor(
    @InjectRepository(Article)
    private readonly articlesRepository: Repository<Article>,
    private readonly categoriesService: CategoriesService,
    private readonly usersService: UsersService,
  ) {}

  private toUploadPath(file: Express.Multer.File) {
    return `/uploads/articles/${file.filename}`;
  }

  private sanitizeUploadPath(path: string) {
    return path.startsWith('/uploads/articles/') ? path : '';
  }

  private async removeLocalImages(paths: string[]) {
    await Promise.all(
      paths.map(async (path) => {
        const safePath = this.sanitizeUploadPath(path);
        if (!safePath) {
          return;
        }

        const relative = safePath.replace('/uploads/', '');
        const fullPath = join(process.cwd(), 'uploads', ...relative.split('/'));
        try {
          await unlink(fullPath);
        } catch {
          // Ignore missing file errors to keep update resilient.
        }
      }),
    );
  }

  findAllPublished() {
    return this.articlesRepository.find({
      where: { status: ArticleStatus.PUBLISHED },
      relations: {
        author: true,
        categories: true,
      },
      order: { featured: 'DESC', createdAt: 'DESC' },
    });
  }

  findAllForAdmin() {
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
      where: { slug, status: ArticleStatus.PUBLISHED },
      relations: { author: true, categories: true },
    });

    if (!article) {
      throw new NotFoundException('Article not found');
    }

    return article;
  }

  private mapGalleryImages(files: Express.Multer.File[], alts: string[]): GalleryImage[] {
    if (files.length !== alts.length) {
      throw new BadRequestException('Each gallery image must have an alt text');
    }

    return files.map((file, index) => {
      const alt = (alts[index] ?? '').trim();
      if (!alt) {
        throw new BadRequestException('Gallery image alt text is required');
      }

      return {
        path: this.toUploadPath(file),
        alt,
      };
    });
  }

  async create(dto: CreateArticleDto, userId: number, files: UploadedArticleFiles) {
    const existingSlug = await this.articlesRepository.findOne({ where: { slug: dto.slug } });
    if (existingSlug) {
      throw new BadRequestException('Article slug already exists');
    }

    const user = await this.usersService.findById(userId);
    if (!user) {
      throw new NotFoundException('Author not found');
    }

    const categories = await this.categoriesService.findByIds(dto.categoryIds);

    const coverFile = files.coverImage?.[0];
    if (!coverFile) {
      throw new BadRequestException('Cover image is required');
    }

    const coverImageAlt = (dto.coverImageAlt ?? '').trim();
    if (!coverImageAlt) {
      throw new BadRequestException('Cover image alt text is required');
    }

    const galleryFiles = files.galleryImages ?? [];
    const galleryAlts = dto.galleryAlts ?? [];
    const galleryImages = this.mapGalleryImages(galleryFiles, galleryAlts);

    const article = this.articlesRepository.create({
      title: dto.title,
      content: dto.content,
      slug: dto.slug,
      coverImagePath: this.toUploadPath(coverFile),
      coverImageAlt,
      galleryImages,
      metaTitle: dto.metaTitle,
      metaDescription: dto.metaDescription,
      metaKeywords: dto.metaKeywords,
      status: ArticleStatus.PUBLISHED,
      featured: dto.featured ?? false,
      author: user,
      categories,
    });

    return this.articlesRepository.save(article);
  }

  async update(id: number, dto: UpdateArticleDto, files: UploadedArticleFiles) {
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

    const removedGalleryPaths = (dto.removedGalleryPaths ?? []).filter((path) =>
      this.sanitizeUploadPath(path),
    );
    const currentGallery = article.galleryImages ?? [];
    const remainingGallery = currentGallery.filter(
      (item) => !removedGalleryPaths.includes(item.path),
    );

    if (removedGalleryPaths.length > 0) {
      await this.removeLocalImages(removedGalleryPaths);
    }

    const newGalleryFiles = files.galleryImages ?? [];
    const galleryAlts = dto.galleryAlts ?? [];
    const newGalleryImages = this.mapGalleryImages(newGalleryFiles, galleryAlts);

    let nextCoverImagePath = article.coverImagePath;
    let nextCoverImageAlt = article.coverImageAlt;

    if (dto.removeCoverImage) {
      if (nextCoverImagePath) {
        await this.removeLocalImages([nextCoverImagePath]);
      }
      nextCoverImagePath = null;
      nextCoverImageAlt = null;
    }

    const newCoverFile = files.coverImage?.[0];
    if (newCoverFile) {
      const coverImageAlt = (dto.coverImageAlt ?? '').trim();
      if (!coverImageAlt) {
        throw new BadRequestException('Cover image alt text is required when replacing cover image');
      }

      if (nextCoverImagePath) {
        await this.removeLocalImages([nextCoverImagePath]);
      }

      nextCoverImagePath = this.toUploadPath(newCoverFile);
      nextCoverImageAlt = coverImageAlt;
    } else if (typeof dto.coverImageAlt === 'string') {
      const coverImageAlt = dto.coverImageAlt.trim();
      if (!coverImageAlt) {
        throw new BadRequestException('Cover image alt text cannot be empty');
      }
      nextCoverImageAlt = coverImageAlt;
    }

    if (!nextCoverImagePath || !nextCoverImageAlt) {
      throw new BadRequestException('Cover image and its alt text are required');
    }

    Object.assign(article, {
      title: dto.title ?? article.title,
      content: dto.content ?? article.content,
      slug: dto.slug ?? article.slug,
      metaTitle: dto.metaTitle ?? article.metaTitle,
      metaDescription: dto.metaDescription ?? article.metaDescription,
      metaKeywords: dto.metaKeywords ?? article.metaKeywords,
      status: ArticleStatus.PUBLISHED,
      featured: dto.featured ?? article.featured,
      coverImagePath: nextCoverImagePath,
      coverImageAlt: nextCoverImageAlt,
      galleryImages: [...remainingGallery, ...newGalleryImages],
    });

    return this.articlesRepository.save(article);
  }

  async remove(id: number) {
    const article = await this.articlesRepository.findOne({ where: { id } });
    if (!article) {
      throw new NotFoundException('Article not found');
    }

    await this.removeLocalImages([
      ...(article.coverImagePath ? [article.coverImagePath] : []),
      ...((article.galleryImages ?? []).map((item) => item.path)),
    ]);
    await this.articlesRepository.remove(article);
    return { message: 'Article deleted' };
  }
}
