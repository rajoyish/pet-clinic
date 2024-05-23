<?php

namespace App\Filament\Resources;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Role;
use App\Models\Slot;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static function form(Form $form): Form
    {
        $doctorRole = Role::whereName('doctor')->first();

        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\Select::make('pet_id')
                        ->relationship('pet', 'name')
                        ->native(false)
                        ->required()
                        ->preload()
                        ->searchable(),
                    Forms\Components\DatePicker::make('date')
                        ->native(false)
                        ->live()
                        ->required()
                        ->closeOnDateSelection(),
                    Forms\Components\Select::make('doctor')
                        ->options(function (Get $get) use ($doctorRole) {
                            return User::whereBelongsTo($doctorRole)
                                ->whereHas('schedules', function (Builder $query) use ($get) {
                                    $query->where('date', $get('date'));
                                })
                                ->get()
                                ->pluck('name', 'id');
                        })
                        ->native(false)
                        ->hidden(fn (Get $get) => blank($get('date'))),
                    Forms\Components\Select::make('slot_id')
                        ->native(false)
                        ->relationship(name: 'slot', titleAttribute: 'start')
                        ->getOptionLabelFromRecordUsing(fn (Slot $record) => $record->start->format('h:i A')),
                    Forms\Components\TextInput::make('description')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->native(false)
                        ->options(AppointmentStatus::class)
                        ->visibleOn(Pages\EditAppointment::class),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pet.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start')
                    ->label('From')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end')
                    ->label('To')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('Confirm')
                    ->action(function (Appointment $record) {
                        $record->status = AppointmentStatus::Confirmed;
                        $record->save();
                    })
                    ->visible(fn (Appointment $record) => $record->status === AppointmentStatus::Created)
                    ->color('success')
                    ->icon('heroicon-o-check'),
                Tables\Actions\Action::make('Cancel')
                    ->action(function (Appointment $record) {
                        $record->status = AppointmentStatus::Canceled;
                        $record->save();
                    })
                    ->visible(fn (Appointment $record) => $record->status !== AppointmentStatus::Canceled)

                    ->color('danger')
                    ->icon('heroicon-o-x-mark'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
