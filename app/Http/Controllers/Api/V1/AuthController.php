<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Prumo API',
    version: '1.0.0',
    description: 'REST API do Prumo — gestão financeira em grupo para o bot (smartphone).',
    contact: new OA\Contact(email: 'suporte@prumo.com.br')
)]
#[OA\Server(url: '/api/v1')]
#[OA\SecurityScheme(securityScheme: 'bearerAuth', type: 'http', scheme: 'bearer', bearerFormat: 'JWT')]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/token',
        summary: 'Emite token de API',
        description: 'Autentica com e-mail + senha e retorna um Bearer token (Sanctum).',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'device_name'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@email.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'Senha@123'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'bot'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token emitido'),
            new OA\Response(response: 422, description: 'Credenciais inválidas'),
        ]
    )]
    public function token(Request $request): JsonResponse
    {
        $data = $request->validate(
            [
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
                'device_name' => ['required', 'string', 'max:255'],
            ],
            [
                'email.required' => 'Informe o e-mail.',
                'password.required' => 'Informe a senha.',
                'device_name.required' => 'Informe o nome do dispositivo.',
            ]
        );

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas. Verifique o e-mail e a senha e tente de novo.'], 422);
        }

        $token = $user->createToken($data['device_name'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/auth/token',
        summary: 'Revoga o token atual',
        description: 'Encerra a sessão revogando o token usado na requisição.',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Sessão encerrada'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sessão encerrada.']);
    }
}
