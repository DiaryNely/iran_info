import {
  ArrayNotEmpty,
  IsArray,
  IsBoolean,
  IsInt,
  IsOptional,
  IsString,
  Matches,
  MaxLength,
  MinLength,
} from 'class-validator';
import { Transform } from 'class-transformer';

export class UpdateArticleDto {
  @IsOptional()
  @IsString()
  @MinLength(5)
  @MaxLength(255)
  title?: string;

  @IsOptional()
  @IsString()
  @MinLength(30)
  content?: string;

  @IsOptional()
  @IsString()
  @MinLength(5)
  @MaxLength(180)
  @Matches(/^[a-z0-9]+(?:-[a-z0-9]+)*$/)
  slug?: string;

  @IsOptional()
  @IsString()
  @MinLength(50)
  @MaxLength(60)
  metaTitle?: string;

  @IsOptional()
  @IsString()
  @MinLength(150)
  @MaxLength(160)
  metaDescription?: string;

  @IsOptional()
  @IsString()
  @MaxLength(255)
  metaKeywords?: string;



  @Transform(({ value }) => {
    if (typeof value === 'boolean') {
      return value;
    }

    if (typeof value === 'string') {
      return value === 'true' || value === '1';
    }

    return Boolean(value);
  })
  @IsOptional()
  @IsBoolean()
  featured?: boolean;

  @IsOptional()
  @IsString()
  @MinLength(3)
  @MaxLength(160)
  coverImageAlt?: string;

  @Transform(({ value }) => {
    if (Array.isArray(value)) {
      return value.map((item) => Number(item));
    }

    if (typeof value === 'string') {
      try {
        const parsed = JSON.parse(value);
        if (Array.isArray(parsed)) {
          return parsed.map((item) => Number(item));
        }
      } catch {
        return value
          .split(',')
          .map((item) => Number(item.trim()))
          .filter((item) => Number.isInteger(item));
      }
    }

    return value;
  })
  @IsOptional()
  @IsArray()
  @ArrayNotEmpty()
  @IsInt({ each: true })
  categoryIds?: number[];

  @Transform(({ value }) => {
    if (Array.isArray(value)) {
      return value.map((item) => String(item));
    }

    if (typeof value === 'string') {
      try {
        const parsed = JSON.parse(value);
        if (Array.isArray(parsed)) {
          return parsed.map((item) => String(item));
        }
      } catch {
        return value
          .split(',')
          .map((item) => item.trim())
          .filter(Boolean);
      }
    }

    return value;
  })
  @IsOptional()
  @IsArray()
  @IsString({ each: true })
  removedGalleryPaths?: string[];

  @Transform(({ value }) => {
    if (typeof value === 'boolean') {
      return value;
    }

    if (typeof value === 'string') {
      return value === 'true' || value === '1';
    }

    return Boolean(value);
  })
  @IsOptional()
  @IsBoolean()
  removeCoverImage?: boolean;

  @Transform(({ value }) => {
    if (Array.isArray(value)) {
      return value.map((item) => String(item));
    }

    if (typeof value === 'string') {
      try {
        const parsed = JSON.parse(value);
        if (Array.isArray(parsed)) {
          return parsed.map((item) => String(item));
        }
      } catch {
        return value
          .split(',')
          .map((item) => item.trim())
          .filter(Boolean);
      }
    }

    return value;
  })
  @IsOptional()
  @IsArray()
  @IsString({ each: true })
  @MinLength(3, { each: true })
  @MaxLength(160, { each: true })
  galleryAlts?: string[];
}
