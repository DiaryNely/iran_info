import { ValidationPipe } from '@nestjs/common';
import { NestFactory } from '@nestjs/core';
import { mkdirSync } from 'fs';
import { join } from 'path';
import { static as serveStatic } from 'express';
import { AppModule } from './app.module';
import { HttpExceptionFilter } from './shared/filters/http-exception.filter';

async function bootstrap() {
  const app = await NestFactory.create(AppModule);
  const port = Number(process.env.PORT ?? 3000);
  const uploadsRoot = join(process.cwd(), 'uploads');
  const articleUploads = join(uploadsRoot, 'articles');
  mkdirSync(articleUploads, { recursive: true });
  const configuredOrigins = (process.env.CORS_ORIGIN ?? '')
    .split(',')
    .map((origin) => origin.trim())
    .filter(Boolean);
  const defaultOrigins = ['http://localhost:5173', 'http://localhost:5174'];
  const allowedOrigins = configuredOrigins.length > 0 ? configuredOrigins : defaultOrigins;

  app.setGlobalPrefix('api');
  app.useGlobalPipes(
    new ValidationPipe({
      whitelist: true,
      transform: true,
      forbidNonWhitelisted: true,
    }),
  );
  app.useGlobalFilters(new HttpExceptionFilter());
  app.enableCors({
    origin: allowedOrigins,
    credentials: true,
  });
  app.use('/uploads', serveStatic(uploadsRoot));

  await app.listen(port);
  console.log(`Backend running on http://localhost:${port}/api`);
}

bootstrap();
