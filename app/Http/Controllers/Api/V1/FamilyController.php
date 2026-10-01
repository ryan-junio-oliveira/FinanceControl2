<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InviteRequest;
use App\Http\Requests\MemberRoleRequest;
use App\Http\Resources\InvitationResource;
use App\Http\Resources\UserResource;
use App\Models\Invitation;
use App\Models\User;
use App\Services\FamilyService;
use App\Support\Fin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Family', description: 'Membros e convites da família')]
class FamilyController extends Controller
{
    #[OA\Get(
        path: '/api/v1/family/members',
        summary: 'Lista membros',
        tags: ['Family'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Membros da família')]
    )]
    public function members(FamilyService $service): AnonymousResourceCollection
    {
        return UserResource::collection($service->members(Fin::family()));
    }

    #[OA\Get(
        path: '/api/v1/family/invites',
        summary: 'Lista convites pendentes',
        tags: ['Family'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Convites pendentes')]
    )]
    public function invites(FamilyService $service): AnonymousResourceCollection
    {
        return InvitationResource::collection($service->invites(Fin::family()));
    }

    #[OA\Post(
        path: '/api/v1/family/invites',
        summary: 'Convida membro',
        tags: ['Family'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'role'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Maria'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'role', type: 'string', enum: ['co_admin', 'dependente', 'junior']),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Convite criado')]
    )]
    public function storeInvite(InviteRequest $request, FamilyService $service): JsonResponse
    {
        ['invitation' => $invitation, 'mailed' => $mailed] = $service->invite(Fin::family(), $request->validated());

        return (new InvitationResource($invitation))
            ->additional(['mailed' => $mailed])
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/api/v1/family/invites/{invite}',
        summary: 'Revoga convite',
        tags: ['Family'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'invite', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 204, description: 'Revogado')]
    )]
    public function revokeInvite(Invitation $invite, FamilyService $service): JsonResponse
    {
        abort_unless($invite->family_id === Fin::familyId(), 404);
        $service->revokeInvite($invite);

        return response()->json(null, 204);
    }

    #[OA\Delete(
        path: '/api/v1/family/members/{member}',
        summary: 'Remove membro',
        tags: ['Family'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'member', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 204, description: 'Removido')]
    )]
    public function removeMember(User $member, FamilyService $service): JsonResponse
    {
        abort_unless($member->family_id === Fin::familyId(), 404);
        $service->removeMember($member, request()->user()->id);

        return response()->json(null, 204);
    }

    #[OA\Patch(
        path: '/api/v1/family/members/{member}/role',
        summary: 'Altera papel do membro',
        tags: ['Family'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'member', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['role'],
                properties: [
                    new OA\Property(property: 'role', type: 'string', enum: ['co_admin', 'dependente', 'junior']),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Papel atualizado')]
    )]
    public function updateRole(MemberRoleRequest $request, User $member, FamilyService $service): UserResource
    {
        abort_unless($member->family_id === Fin::familyId(), 404);

        return new UserResource($service->updateRole($member, $request->validated()));
    }
}
