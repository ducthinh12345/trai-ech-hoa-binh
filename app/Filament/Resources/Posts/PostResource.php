<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Support\SeoFields;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung website';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'bài viết';

    protected static ?string $pluralModelLabel = 'Bài viết / Tin tức';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make()->schema([
                    TextInput::make('title')->label('Tiêu đề')->required()->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                    TextInput::make('slug')->label('Đường dẫn')->prefix('/tin-tuc/')->unique(ignoreRecord: true),
                    Textarea::make('excerpt')->label('Tóm tắt')->rows(2)->maxLength(500)->helperText('Hiện ở danh sách bài và đầu bài viết.'),
                    RichEditor::make('content')->label('Nội dung')
                        ->fileAttachmentsDisk('public')->fileAttachmentsDirectory('posts/content')->fileAttachmentsVisibility('public')
                        ->toolbarButtons([
                            ['bold', 'italic', 'underline', 'link'],
                            ['h2', 'h3'],
                            ['alignStart', 'alignCenter'],
                            ['bulletList', 'orderedList', 'blockquote'],
                            ['table', 'attachFiles'],
                            ['undo', 'redo'],
                        ])
                        ->extraInputAttributes(['style' => 'min-height: 24rem']),
                ]),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Xuất bản')->schema([
                    Toggle::make('is_published')->label('Đăng công khai')->default(true),
                    Toggle::make('is_featured')->label('Bài nổi bật'),
                    DateTimePicker::make('published_at')->label('Ngày đăng')->native(false)->displayFormat('d/m/Y H:i')->default(now())
                        ->helperText('Chọn ngày tương lai để hẹn giờ đăng.'),
                    Select::make('post_category_id')->label('Chuyên mục')->relationship('category', 'name')->preload()
                        ->createOptionForm([TextInput::make('name')->label('Tên chuyên mục')->required()]),
                ]),
                Section::make('Ảnh bìa')->schema([
                    FileUpload::make('cover_image')->hiddenLabel()->image()->disk('public')->directory('posts')->imageEditor()
                        ->imageEditorAspectRatioOptions(['16:9']),
                ]),
                SeoFields::section('title', 'excerpt', 'tin-tuc'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category'))
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('cover_image')->label('Ảnh')->disk('public')->imageHeight(44)->imageWidth(70),
                TextColumn::make('title')->label('Tiêu đề')->searchable()->weight('semibold')->wrap()->limit(80)
                    ->description(fn (Post $r) => $r->category?->name),
                TextColumn::make('published_at')->label('Ngày đăng')->dateTime()->sortable()
                    ->description(fn (Post $r) => $r->published_at?->isFuture() ? '⏰ Hẹn giờ' : null),
                TextColumn::make('views_count')->label('Lượt xem')->numeric()->sortable(),
                ToggleColumn::make('is_published')->label('Công khai'),
            ])
            ->filters([SelectFilter::make('post_category_id')->label('Chuyên mục')->relationship('category', 'name')])
            ->recordActions([
                Action::make('view')->label('Xem')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')->url(fn (Post $r) => $r->url())->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
