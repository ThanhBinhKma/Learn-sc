<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateUser extends Command
{
    protected $signature = 'user:create {email : Email đăng nhập} {--name= : Tên hiển thị} {--password= : Mật khẩu (nếu bỏ trống sẽ tự sinh)}';

    protected $description = 'Create a local user for login';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = (string) ($this->option('name') ?: 'Admin');
        $password = (string) ($this->option('password') ?: Str::random(14));

        if (User::query()->where('email', $email)->exists()) {
            $this->error('Email đã tồn tại.');
            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info('Tạo user thành công.');
        $this->line('Email: '.$email);
        $this->line('Password: '.$password);

        return self::SUCCESS;
    }
}

