<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 首次启动时如果 users 表为空，自动建一个管理员
        if (User::count() === 0) {
            $cfg = config('chaqin.admin');
            User::create([
                'name'     => $cfg['name'],
                'email'    => $cfg['email'],
                'password' => Hash::make($cfg['password']),
                'role'     => 'admin',
            ]);
            $this->command?->info("已创建初始管理员: {$cfg['email']} / {$cfg['password']}");
        }
    }
}
