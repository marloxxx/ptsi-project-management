<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Jeffgreco13\FilamentBreezy\Pages\MyProfilePage as BreezyProfilePage;

class Profile extends BreezyProfilePage implements HasForms
{
    use InteractsWithForms;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'My Profile';

    protected static ?string $title = 'My Profile';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.profile';

    public ?array $data = [];

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->form->fill([
            'avatar' => $user->avatar,
            'full_name' => $user->full_name ?? $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'status' => $user->status ?? 'active',
            'position' => $user->position,
            'position_level' => $user->position_level,
            'employee_status' => $user->employee_status,
            'nik' => $user->nik,
            'phone' => $user->phone,
            'place_of_birth' => $user->place_of_birth,
            'date_of_birth' => optional($user->date_of_birth)?->toDateString(),
            'gender' => $user->gender,
            'preferred_language' => $user->preferred_language ?? 'id',
            'city' => $user->city,
            'address' => $user->address,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('profileTabs')
                    ->tabs([
                        Tab::make('Profil')
                            ->schema([
                                Section::make('Informasi Akun')
                                    ->schema([
                                        FileUpload::make('avatar')
                                            ->image()
                                            ->imageEditor()
                                            ->avatar()
                                            ->directory('avatars')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->columnSpanFull(),
                                        TextInput::make('full_name')
                                            ->label('Nama lengkap')
                                            ->required()
                                            ->maxLength(120),
                                        TextInput::make('username')
                                            ->label('Username')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->helperText('Username mengikuti data SI Portal.'),
                                        TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->required()
                                            ->maxLength(120)
                                            ->rules([
                                                Rule::unique('users', 'email')->ignore(Auth::id()),
                                            ]),
                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'active' => 'Aktif',
                                                'inactive' => 'Nonaktif',
                                            ])
                                            ->default('active')
                                            ->required()
                                            ->native(false),
                                    ])->columns(2),
                                Section::make('Data Personal')
                                    ->schema([
                                        TextInput::make('nik')
                                            ->label('NIK')
                                            ->maxLength(30)
                                            ->rules([
                                                'nullable',
                                                'string',
                                                'max:30',
                                                Rule::unique('users', 'nik')->ignore(Auth::id()),
                                            ]),
                                        TextInput::make('position')
                                            ->label('Jabatan')
                                            ->maxLength(120),
                                        TextInput::make('position_level')
                                            ->label('Level Jabatan')
                                            ->maxLength(120),
                                        TextInput::make('employee_status')
                                            ->label('Status Karyawan')
                                            ->maxLength(120),
                                        TextInput::make('place_of_birth')
                                            ->label('Tempat lahir')
                                            ->maxLength(120),
                                        DatePicker::make('date_of_birth')
                                            ->label('Tanggal lahir')
                                            ->maxDate(now())
                                            ->displayFormat('d F Y')
                                            ->native(false),
                                        Select::make('gender')
                                            ->label('Jenis kelamin')
                                            ->options([
                                                'Pria' => 'Pria',
                                                'Wanita' => 'Wanita',
                                            ])
                                            ->native(false),
                                        Select::make('preferred_language')
                                            ->label('Bahasa')
                                            ->options([
                                                'id' => 'Bahasa Indonesia',
                                                'en' => 'English',
                                            ])
                                            ->native(false),
                                    ])->columns(2),
                                Section::make('Kontak & Alamat')
                                    ->schema([
                                        TextInput::make('phone')
                                            ->label('Nomor telepon')
                                            ->tel()
                                            ->maxLength(30),
                                        TextInput::make('city')
                                            ->label('Kota')
                                            ->maxLength(120),
                                        Textarea::make('address')
                                            ->label('Alamat')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ])->columns(2),
                            ]),
                        Tab::make('Keamanan')
                            ->schema([
                                Section::make('Ubah Password')
                                    ->description('Perbarui password akun Anda.')
                                    ->schema([
                                        TextInput::make('current_password')
                                            ->password()
                                            ->revealable()
                                            ->label('Password saat ini'),
                                        TextInput::make('new_password')
                                            ->password()
                                            ->revealable()
                                            ->label('Password baru')
                                            ->minLength(8)
                                            ->same('new_password_confirmation'),
                                        TextInput::make('new_password_confirmation')
                                            ->password()
                                            ->revealable()
                                            ->label('Konfirmasi password baru'),
                                    ])->columns(2),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $dateOfBirth = Arr::get($state, 'date_of_birth') ?: null;

        $user->forceFill([
            'name' => Arr::get($state, 'full_name', $user->name),
            'full_name' => Arr::get($state, 'full_name'),
            'email' => Arr::get($state, 'email'),
            'status' => Arr::get($state, 'status', 'active'),
            'position' => Arr::get($state, 'position'),
            'position_level' => Arr::get($state, 'position_level'),
            'employee_status' => Arr::get($state, 'employee_status'),
            'nik' => Arr::get($state, 'nik'),
            'phone' => Arr::get($state, 'phone'),
            'place_of_birth' => Arr::get($state, 'place_of_birth'),
            'date_of_birth' => $dateOfBirth,
            'gender' => Arr::get($state, 'gender'),
            'preferred_language' => Arr::get($state, 'preferred_language', 'id'),
            'city' => Arr::get($state, 'city'),
            'address' => Arr::get($state, 'address'),
            'avatar' => Arr::get($state, 'avatar'),
        ])->save();

        if (! empty($state['new_password'])) {
            if (! Hash::check($state['current_password'] ?? '', $user->getAuthPassword())) {
                Notification::make()
                    ->title('Password saat ini salah')
                    ->danger()
                    ->send();

                return;
            }

            $user->forceFill([
                'password' => Hash::make($state['new_password']),
            ])->save();
        }

        Notification::make()
            ->title('Profil berhasil diperbarui')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan perubahan')
                ->keyBindings(['mod+s'])
                ->action('save')
                ->color('primary'),
        ];
    }
}
