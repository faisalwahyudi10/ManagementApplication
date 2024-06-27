<?php

namespace App\Filament\Pages;

use App\Models;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Infolists;
use Filament\Notifications;
use Filament\Pages\Page;
use Illuminate\Support;

class Account extends Page implements Forms\Contracts\HasForms, Actions\Contracts\HasActions
{
    use Forms\Concerns\InteractsWithForms, Actions\Concerns\InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-s-user-circle';
    protected static string $view = 'filament.pages.account';
    protected ?string $heading = ' ';
    public Models\User $user;
    public array $avatar = [];

    public function getBreadcrumbs(): array
    {
        return [
            route($this->getRouteName(), [$this->user->id]) => 'Accounts',
            $this->user->name,
        ];
    }

    public function updateAvatar(): void
    {
        try {
            $this->form->model($this->user)->saveRelationships();

            $this->user->refresh();

            $this->dispatch('close-modal', id: 'upload-avatar-modal');

            Notifications\Notification::make()
                ->title('Foto profil berhasil diperbarui')
                ->success()
                ->send();
        } catch (\Throwable $th) {
            Notifications\Notification::make()
                ->title('Gagal memperbarui foto profil')
                ->body($th->getMessage())
                ->danger()
                ->send();
        }
    }

    public function updateUserData(array $data = []): void
    {
        $this->user->update($data);
    }

    public function editProfileAction(): Actions\Action
    {
        return Actions\Action::make('editProfileAction')
            ->label('Edit Profil')
            ->outlined()
            ->visible(fn () => auth()->user()->is($this->user))
            ->icon('heroicon-s-pencil-square')
            ->extraAttributes(['class' => 'w-fit !text-xs'])
            ->size('sm')
            ->fillForm($this->user->toArray())
            ->form([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->placeholder('Masukkan nama lengkap'),
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->placeholder('Masukkan alamat email')
                    ->email()
                    ->unique('users', 'email', $this->user),
            ])
            ->modalSubmitActionLabel('Simpan')
            ->successNotificationTitle('Profil berhasil diperbarui')
            ->failureNotificationTitle('Gagal memperbarui profil')
            ->action(function (array $data, Actions\Action $action) {
                try {
                    $this->updateUserData($data);

                    $action->success();
                } catch (\Throwable $th) {
                    $action->failure();
                }
            });
    }

    public function changePasswordAction(): Actions\Action
    {
        return Actions\Action::make('changePasswordAction')
            ->label('Ubah Password')
            ->visible(fn () => auth()->user()->is($this->user))
            ->outlined()
            ->icon('heroicon-s-key')
            ->extraAttributes(['class' => 'w-fit !text-xs'])
            ->form([
                Forms\Components\TextInput::make('password')
                    ->required()
                    ->password()
                    ->label('Password Baru')
                    ->placeholder('Masukkan password baru')
                    ->dehydrateStateUsing(fn ($state) => Support\Facades\Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->confirmed(),
                Forms\Components\TextInput::make('password_confirmation')
                    ->requiredWith('password')
                    ->placeholder('Konfirmasi password baru')
                    ->label('Konfirmasi Password')
                    ->password()
                    ->dehydrated(false),
            ])
            ->successNotificationTitle('Password berhasil diperbarui')
            ->failureNotificationTitle('Gagal memperbarui password')
            ->modalSubmitActionLabel('Simpan')
            ->size('sm')
            ->action(function (array $data, Actions\Action $action) {
                try {
                    $this->updateUserData($data);

                    if (request()->hasSession() && array_key_exists('password', $data)) {
                        request()->session()->put([
                            'password_hash_' . Filament::getAuthGuard() => $data['password'],
                        ]);
                    }

                    $action->success();
                } catch (\Throwable $th) {
                    $action->failure();
                }
            });
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\SpatieMediaLibraryFileUpload::make('avatar.profile')
                    ->label('Upload Foto Profil')
                    ->hiddenLabel()
                    ->collection('profile')
                    ->model($this->user)
                    ->image()
                    ->required()
                    ->maxSize(2048)
                    ->columnSpanFull()
                    ->preserveFilenames()
                    ->imageEditor(),
            ]);
    }

    public function mount(Models\User $user): void
    {
        $this->user = $user;

        $this->form->fill($this->user->attributesToArray());
    }

    public static function getRoutePath(): string
    {
        return '/' . static::getSlug(). '/{user}';
    }

    public static function getHtmlLabelEntryString(string $label): string
    {
        return '<span class="text-sm text-gray-600/70 dark:text-gray-50/70">'.$label.'</span>';
    }

    public function profileInfolist(Infolists\Infolist $infolist): Infolists\Infolist
    {
        return $infolist
            ->record($this->user)
            ->schema([
                Infolists\Components\Tabs::make()
                    ->schema([
                        Infolists\Components\Tabs\Tab::make('Informasi')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label(fn () => new Support\HtmlString(static::getHtmlLabelEntryString('Nama')))
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                    ->icon('heroicon-s-user-circle')
                                    ->iconColor('primary'),
                                Infolists\Components\TextEntry::make('is_active')
                                    ->label(fn () => new Support\HtmlString(static::getHtmlLabelEntryString('Status')))
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                    ->icon(fn (int $state): string => Models\Enums\UserStatus::getIcon($state))
                                    ->color(fn (int $state): string => Models\Enums\UserStatus::getColor($state))
                                    ->formatStateUsing(fn (int $state): string => Models\Enums\UserStatus::getName($state))
                                    ->badge(),
                                Infolists\Components\TextEntry::make('email')
                                    ->label(fn () => new Support\HtmlString(static::getHtmlLabelEntryString('Email')))
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                    ->icon('heroicon-s-envelope')
                                    ->iconColor('primary'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label(fn () => new Support\HtmlString(static::getHtmlLabelEntryString('Bergabung Pada')))
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                    ->icon('heroicon-s-calendar-days')
                                    ->iconColor('primary')
                                    ->formatStateUsing(function ($state) {
                                        \Carbon\Carbon::setLocale('id');
                                        return \Carbon\Carbon::parse($state)->isoFormat('D MMMM Y');
                                    }),
                                Infolists\Components\TextEntry::make('roles.name')
                                    ->label(fn () => new Support\HtmlString(static::getHtmlLabelEntryString('Peran')))
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                    ->color('primary')
                                    ->badge(),
                            ])
                            ->columns(2),
                    ])
                    ->persistTab()
                    ->persistTabInQueryString()
            ]);
    }
}