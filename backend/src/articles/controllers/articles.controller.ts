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
} from '@nestjs/common';
import { CurrentUser } from '../../shared/decorators/current-user.decorator';
import { JwtAuthGuard } from '../../shared/guards/jwt-auth.guard';
import { JwtUser } from '../../shared/types/jwt-user.type';
import { CreateArticleDto } from '../dto/create-article.dto';
import { UpdateArticleDto } from '../dto/update-article.dto';
import { ArticlesService } from '../services/articles.service';

@Controller()
export class ArticlesController {
  constructor(private readonly articlesService: ArticlesService) {}

  @Get('articles')
  findAll() {
    return this.articlesService.findAll();
  }

  @Get('article/:slug')
  findOneBySlug(@Param('slug') slug: string) {
    return this.articlesService.findOneBySlug(slug);
  }

  @UseGuards(JwtAuthGuard)
  @Post('articles')
  create(@Body() dto: CreateArticleDto, @CurrentUser() user: JwtUser) {
    return this.articlesService.create(dto, user.sub);
  }

  @UseGuards(JwtAuthGuard)
  @Patch('articles/:id')
  update(@Param('id', ParseIntPipe) id: number, @Body() dto: UpdateArticleDto) {
    return this.articlesService.update(id, dto);
  }

  @UseGuards(JwtAuthGuard)
  @Delete('articles/:id')
  remove(@Param('id', ParseIntPipe) id: number) {
    return this.articlesService.remove(id);
  }
}
