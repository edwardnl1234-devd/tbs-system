<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => 'string',
            'status' => 'string',
        ];
    }

    // Role check methods
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isMandor(): bool
    {
        return $this->role === 'mandor';
    }

    public function hasRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    // Relationships
    public function weighingsAsOperator()
    {
        return $this->hasMany(Weighing::class, 'operator_id');
    }

    public function sortationsAsMandor()
    {
        return $this->hasMany(Sortation::class, 'mandor_id');
    }

    public function productionsAsSupervisor()
    {
        return $this->hasMany(Production::class, 'supervisor_id');
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class, 'adjusted_by');
    }

    public function approvedAdjustments()
    {
        return $this->hasMany(StockAdjustment::class, 'approved_by');
    }

    public function stockOpnames()
    {
        return $this->hasMany(StockOpname::class, 'counted_by');
    }

    public function tbsPrices()
    {
        return $this->hasMany(TbsPrice::class, 'set_by');
    }
}
