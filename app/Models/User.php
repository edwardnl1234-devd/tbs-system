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
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isAccounting(): bool
    {
        return $this->role === 'accounting';
    }

    public function isFinance(): bool
    {
        return $this->role === 'finance';
    }

    public function isOperatorTimbangan(): bool
    {
        return $this->role === 'operator_timbangan';
    }

    /**
     * Check if user can edit data (only admin can edit)
     */
    public function canEdit(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can add data
     * Admin, Accounting, Finance, Operator Timbangan can add
     * Manager can only view
     */
    public function canAdd(): bool
    {
        return in_array($this->role, ['admin', 'accounting', 'finance', 'operator_timbangan']);
    }

    /**
     * Check if user can view financial data (purchases/sales)
     */
    public function canViewFinancial(): bool
    {
        return in_array($this->role, ['admin', 'manager', 'accounting', 'finance']);
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
