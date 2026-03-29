import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { AuthModule } from '../auth/auth.module';
import { CategoriesModule } from '../categories/categories.module';
import { JwtAuthGuard } from '../shared/guards/jwt-auth.guard';
import { UsersModule } from '../users/users.module';
import { ArticlesController } from './controllers/articles.controller';
import { Article } from './entities/article.entity';
import { ArticlesService } from './services/articles.service';

@Module({
  imports: [
    TypeOrmModule.forFeature([Article]),
    AuthModule,
    CategoriesModule,
    UsersModule,
  ],
  controllers: [ArticlesController],
  providers: [ArticlesService, JwtAuthGuard],
  exports: [ArticlesService],
})
export class ArticlesModule {}
