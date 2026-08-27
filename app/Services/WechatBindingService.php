<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 微信身份 -> 学生 的绑定与解析
 *
 * 第一版用 cookie 里的 mock openid，等同于微信 openid 的语义占位。
 * 第二版替换为真微信 OAuth 后，本服务的所有方法无需修改。
 */
class WechatBindingService
{
    public function __construct(private readonly Request $request) {}

    public function currentOpenId(): ?string
    {
        return $this->request->attributes->get('mock_openid');
    }

    /**
     * 查找当前 openid 已绑定的学生
     */
    public function currentStudent(): ?Student
    {
        $openid = $this->currentOpenId();
        if (!$openid) return null;
        return Student::where('openid', $openid)->first();
    }

    /**
     * 尝试把当前 openid 绑定到指定学生
     *
     * 业务约束：
     *   1) 一个 openid 只能绑一个学生
     *   2) 一个学生不能同时被另一个 openid 占用
     *   3) 学生必须 active
     */
    public function bind(int $studentId): array
    {
        $openid = $this->currentOpenId();
        if (!$openid) {
            return ['ok' => false, 'code' => 'no_openid', 'message' => '未识别到身份标识，请重新打开链接'];
        }

        return DB::transaction(function () use ($openid, $studentId) {
            // 已被当前 openid 绑定的学生
            $existingSelf = Student::where('openid', $openid)->lockForUpdate()->first();
            if ($existingSelf && $existingSelf->id === $studentId) {
                return ['ok' => true, 'student' => $existingSelf, 'already' => true];
            }

            $student = Student::lockForUpdate()->find($studentId);
            if (!$student) {
                return ['ok' => false, 'code' => 'not_found', 'message' => '学生信息不存在'];
            }
            if (!$student->isActive()) {
                return ['ok' => false, 'code' => 'disabled', 'message' => '该账号已停用，请联系老师'];
            }
            if (!empty($student->openid) && $student->openid !== $openid) {
                return ['ok' => false, 'code' => 'taken', 'message' => '该学生已被其他微信绑定，请联系老师解绑'];
            }
            if ($existingSelf && $existingSelf->id !== $studentId) {
                // 当前 openid 已绑其他人，需先解绑 (UI 应不出现此分支)
                return ['ok' => false, 'code' => 'self_taken', 'message' => '当前微信已绑定其他学生'];
            }

            $student->openid = $openid;
            $student->bound_at = now();
            $student->save();

            return ['ok' => true, 'student' => $student, 'already' => false];
        });
    }

    /**
     * 管理员解绑某学生 (后台操作)
     */
    public function unbind(Student $student): void
    {
        $student->openid = null;
        $student->bound_at = null;
        $student->save();
    }
}
