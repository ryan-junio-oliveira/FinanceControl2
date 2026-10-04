<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InviteRequest;
use App\Http\Requests\MemberRoleRequest;
use App\Http\Resources\InvitationResource;
use App\Http\Resources\UserResource;
use App\Models\Invitation;
use App\Models\User;
use App\Services\GroupService;
use App\Support\Fin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Group', description: 'Membros e convites do grupo')]
class GroupController extends Controller
{
    #[OA\Get(
        path: '/api/v1/group/members',
        summary: 'Lista membros',
        tags: ['Group'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Membros do grupo')]
    )]
    public function members(GroupService $service): AnonymousResourceCollection
    {
        return UserResource::collection($service->members(Fin::group()));
    }

    #[OA\Get(
        path: '/api/v1/group/invites',
        summary: 'Lista convites pendentes',
        tags: ['Group'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Convites pendentes')]
    )]
    public function invites(GroupService $service): AnonymousResourceCollection
    {
        return InvitationResource::collection($service->invites(Fin::group()));
    }

    #[OA\Post(
        path: '/api/v1/group/invites',
        summary: 'Convida membro',
        tags: ['Group'],
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
    public function storeInvite(InviteRequest $request, GroupService $service): JsonResponse
    {
        ['invitation' => $invitation, 'mailed' => $mailed] = $service->invite(Fin::group(), $request->validated());

        return (new InvitationResource($invitation))
            ->additional(['mailed' => $mailed])
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/api/v1/group/invites/{invite}',
        summary: 'Revoga convite',
        tags: ['Group'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'invite', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 204, description: 'Revogado')]
    )]
    public function revokeInvite(Invitation $invite, GroupService $service): JsonResponse
    {
        abort_unless($invite->group_id === Fin::groupId(), 404);
        $service->revokeInvite($invite);

        return response()->json(null, 204);
    }

    #[OA\Delete(
        path: '/api/v1/group/members/{member}',
        summary: 'Remove membro',
        tags: ['Group'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'member', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 204, description: 'Removido')]
    )]
    public function removeMember(User $member, GroupService $service): JsonResponse
    {
        abort_unless($member->group_id === Fin::groupId(), 404);
        $service->removeMember($member, request()->user()->id);

        return response()->json(null, 204);
    }

    #[OA\Patch(
        path: '/api/v1/group/members/{member}/role',
        summary: 'Altera papel do membro',
        tags: ['Group'],
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
    public function updateRole(MemberRoleRequest $request, User $member, GroupService $service): UserResource
    {
        abort_unless($member->group_id === Fin::groupId(), 404);

        return new UserResource($service->updateRole($member, $request->validated()));
    }
}
