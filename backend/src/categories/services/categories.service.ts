import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CreateCategoryDto } from '../dto/create-category.dto';
import { UpdateCategoryDto } from '../dto/update-category.dto';
import { Category } from '../entities/category.entity';

@Injectable()
export class CategoriesService {
  constructor(
    @InjectRepository(Category)
    private readonly categoriesRepository: Repository<Category>,
  ) {}

  findAll() {
    return this.categoriesRepository.find({ order: { createdAt: 'DESC' } });
  }

  async findOneBySlug(slug: string) {
    const category = await this.categoriesRepository.findOne({ where: { slug } });
    if (!category) {
      throw new NotFoundException('Category not found');
    }
    return category;
  }

  async findByIds(ids: number[]) {
    if (ids.length === 0) {
      return [];
    }

    const categories = await this.categoriesRepository.findBy(ids.map((id) => ({ id })));
    if (categories.length !== ids.length) {
      throw new BadRequestException('One or more category IDs are invalid');
    }

    return categories;
  }

  async create(dto: CreateCategoryDto) {
    const existingSlug = await this.categoriesRepository.findOne({ where: { slug: dto.slug } });
    if (existingSlug) {
      throw new BadRequestException('Category slug already exists');
    }

    const category = this.categoriesRepository.create({
      name: dto.name,
      slug: dto.slug,
      description: dto.description ?? null,
      metaTitle: dto.metaTitle ?? null,
      metaDescription: dto.metaDescription ?? null,
    });

    return this.categoriesRepository.save(category);
  }

  async update(id: number, dto: UpdateCategoryDto) {
    const category = await this.categoriesRepository.findOne({ where: { id } });
    if (!category) {
      throw new NotFoundException('Category not found');
    }

    if (dto.slug && dto.slug !== category.slug) {
      const existingSlug = await this.categoriesRepository.findOne({ where: { slug: dto.slug } });
      if (existingSlug) {
        throw new BadRequestException('Category slug already exists');
      }
    }

    Object.assign(category, {
      ...dto,
      description: dto.description ?? category.description,
      metaTitle: dto.metaTitle ?? category.metaTitle,
      metaDescription: dto.metaDescription ?? category.metaDescription,
    });

    return this.categoriesRepository.save(category);
  }

  async remove(id: number) {
    const category = await this.categoriesRepository.findOne({ where: { id } });
    if (!category) {
      throw new NotFoundException('Category not found');
    }

    await this.categoriesRepository.remove(category);
    return { message: 'Category deleted' };
  }
}
