<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  public function up(): void
  {
    DB::table('users')->where('role', 'admin')->update(['role' => '_role_swap']);
    DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);
    DB::table('users')->where('role', '_role_swap')->update(['role' => 'super_admin']);
  }

  public function down(): void
  {
    DB::table('users')->where('role', 'super_admin')->update(['role' => '_role_swap']);
    DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin']);
    DB::table('users')->where('role', '_role_swap')->update(['role' => 'admin']);
  }
};
