import {
  ArrayNotEmpty,
  IsArray,
  IsInt,
  IsOptional,
  IsString,
  Matches,
  MaxLength,
  MinLength,
} from 'class-validator';

export class CreateArticleDto {
  @IsString()
  @MinLength(5)
  @MaxLength(255)
  title!: string;

  @IsString()
  @MinLength(30)
  content!: string;

  @IsOptional()
  @IsString()
  image?: string;

  @IsString()
  @MinLength(5)
  @MaxLength(180)
  @Matches(/^[a-z0-9]+(?:-[a-z0-9]+)*$/)
  slug!: string;

  @IsOptional()
  @IsString()
  @MaxLength(255)
  metaTitle?: string;

  @IsOptional()
  @IsString()
  @MaxLength(300)
  metaDescription?: string;

  @IsArray()
  @ArrayNotEmpty()
  @IsInt({ each: true })
  categoryIds!: number[];
}
