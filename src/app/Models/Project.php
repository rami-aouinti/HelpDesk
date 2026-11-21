<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Project extends Model {
 use SoftDeletes;
 protected $fillable = ['name','description','cover_image_path','created_by'];
 public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
 public function members(): BelongsToMany { return $this->belongsToMany(User::class)->withTimestamps(); }
 public function tickets(): HasMany { return $this->hasMany(Ticket::class); }
 public function documents(): HasMany { return $this->hasMany(ProjectDocument::class)->latest(); }
}