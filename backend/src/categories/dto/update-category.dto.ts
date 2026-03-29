import {
	IsOptional,
	IsString,
	Matches,
	MaxLength,
	MinLength,
} from 'class-validator';

export class UpdateCategoryDto {
	@IsOptional()
	@IsString()
	@MinLength(2)
	@MaxLength(120)
	name?: string;

	@IsOptional()
	@IsString()
	@MinLength(2)
	@MaxLength(150)
	@Matches(/^[a-z0-9]+(?:-[a-z0-9]+)*$/)
	slug?: string;

	@IsOptional()
	@IsString()
	description?: string;

	@IsOptional()
	@IsString()
	@MaxLength(255)
	metaTitle?: string;

	@IsOptional()
	@IsString()
	@MaxLength(300)
	metaDescription?: string;
}
