<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

/**
 * Khách hàng và tài khoản nhân viên. Nhân viên xem được danh sách khách; chỉ admin mới đổi vai trò.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Khách hàng';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'người dùng';

    protected static ?string $pluralModelLabel = 'Khách hàng & tài khoản';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make('Thông tin')->columns(2)->schema([
                TextInput::make('name')->label('Họ tên')->required(),
                TextInput::make('phone')->label('Số điện thoại')->tel()->unique(ignoreRecord: true),
                TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('password')->label('Mật khẩu')->password()->revealable()
                    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'Để trống nếu không đổi.' : null)
                    ->minLength(8),
            ])->columnSpanFull(),
            Section::make('Phân quyền')->schema([
                ToggleButtons::make('role')->label('Vai trò')->options(UserRole::class)->inline()->required()->default(UserRole::Customer)
                    ->disabled(fn () => ! auth()->user()?->isAdmin())
                    ->helperText('Quản trị viên: toàn quyền. Nhân viên: xử lý đơn, sản phẩm, nội dung (không vào Cài đặt). Khách hàng: chỉ mua hàng.'),
                Toggle::make('is_active')->label('Tài khoản hoạt động')->default(true),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('orders')->withSum(['orders as spent' => fn ($o) => $o->where('status', '!=', 'cancelled')], 'total'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Họ tên')->searchable(['name', 'email', 'phone'])->weight('semibold')->description(fn (User $r) => $r->email),
                TextColumn::make('phone')->label('Điện thoại')->copyable()->placeholder('—'),
                TextColumn::make('role')->label('Vai trò')->badge(),
                TextColumn::make('orders_count')->label('Số đơn')->alignCenter()->sortable(),
                TextColumn::make('spent')->label('Tổng mua')->money()->sortable()->placeholder('0'),
                TextColumn::make('created_at')->label('Ngày tạo')->date()->sortable()->toggleable(),
                ToggleColumn::make('is_active')->label('Hoạt động')->disabled(fn (User $r) => $r->is(auth()->user())),
            ])
            ->filters([SelectFilter::make('role')->label('Vai trò')->options(UserRole::class)])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
