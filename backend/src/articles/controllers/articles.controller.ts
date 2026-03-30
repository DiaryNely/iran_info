import {
  Body,
  Controller,
  Delete,
  Get,
  Param,
  ParseIntPipe,
  Patch,
  Post,
  UseGuards,
  UseInterceptors,
  UploadedFiles,
} from '@nestjs/common';
import { FileFieldsInterceptor } from '@nestjs/platform-express';
import { CurrentUser } from '../../shared/decorators/current-user.decorator';
import { JwtAuthGuard } from '../../shared/guards/jwt-auth.guard';
import { JwtUser } from '../../shared/types/jwt-user.type';
import { articleUploadOptions } from '../articles-upload.config';
import { CreateArticleDto } from '../dto/create-article.dto';
import { UpdateArticleDto } from '../dto/update-article.dto';
import { ArticlesService } from '../services/articles.service';

interface UploadedArticleFiles {
  coverImage?: Express.Multer.File[];
  galleryImages?: Express.Multer.File[];
}

@Controller()
export class ArticlesController {
  constructor(private readonly articlesService: ArticlesService) {}

  @Get('articles')
  findAll() {
    return this.articlesService.findAllPublished();
  }

  @UseGuards(JwtAuthGuard)
  @Get('admin/articles')
  findAllForAdmin() {
    return this.articlesService.findAllForAdmin();
  }

  @Get('article/:slug')
  findOneBySlug(@Param('slug') slug: string) {
    return this.articlesService.findOneBySlug(slug);
  }

  @UseGuards(JwtAuthGuard)
  @Post('articles')
  @UseInterceptors(
    FileFieldsInterceptor(
      [
        { name: 'coverImage', maxCount: 1 },
        { name: 'galleryImages' },
      ],
      articleUploadOptions,
    ),
  )
  create(
    @Body() dto: CreateArticleDto,
    @CurrentUser() user: JwtUser,
    @UploadedFiles() files: UploadedArticleFiles,
  ) {
    return this.articlesService.create(dto, user.sub, files ?? {});
  }

  @UseGuards(JwtAuthGuard)
  @Patch('articles/:id')
  @UseInterceptors(
    FileFieldsInterceptor(
      [
        { name: 'coverImage', maxCount: 1 },
        { name: 'galleryImages' },
      ],
      articleUploadOptions,
    ),
  )
  update(
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: UpdateArticleDto,
    @UploadedFiles() files: UploadedArticleFiles,
  ) {
    return this.articlesService.update(id, dto, files ?? {});
  }

  @UseGuards(JwtAuthGuard)
  @Delete('articles/:id')
  remove(@Param('id', ParseIntPipe) id: number) {
    return this.articlesService.remove(id);
  }
}
