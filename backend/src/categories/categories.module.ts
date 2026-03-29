import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { AuthModule } from '../auth/auth.module';
import { JwtAuthGuard } from '../shared/guards/jwt-auth.guard';
import { CategoriesController } from './controllers/categories.controller';
import { Category } from './entities/category.entity';
import { CategoriesService } from './services/categories.service';

@Module({
  imports: [TypeOrmModule.forFeature([Category]), AuthModule],
  controllers: [CategoriesController],
  providers: [CategoriesService, JwtAuthGuard],
  exports: [CategoriesService, TypeOrmModule],
})
export class CategoriesModule {}
