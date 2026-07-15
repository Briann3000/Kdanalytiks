<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SurveyPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Survey $survey): bool
    {
        // Owners and collaborators
        if ($this->checkOwnershipOrCollaboration($user, $survey)) {
            return true;
        }

        // Respondents who participated
        $roleValue = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
        if ($roleValue === 'respondent') {
            $hasResponded = \App\Models\Response::where('survey_id', $survey->id)
                ->where('respondent_id', $user->id)
                ->exists();

            if ($hasResponded) {
                return true;
            }
        }

        // Public access check
        if ($survey->public_access && $survey->public_access !== 'none') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Survey $survey): bool
    {
        return $this->checkOwnershipOrCollaboration($user, $survey);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Survey $survey): bool
    {
        return $this->checkOwnershipOrCollaboration($user, $survey);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Survey $survey): bool
    {
        return $this->checkOwnershipOrCollaboration($user, $survey);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Survey $survey): bool
    {
        return $this->checkOwnershipOrCollaboration($user, $survey);
    }

    /**
     * Centralized logic for survey ownership and explicit collaboration checks.
     */
    private function checkOwnershipOrCollaboration(User $user, Survey $survey): bool
    {
        // Direct creator
        if ((int) $survey->created_by === (int) $user->id) {
            return true;
        }

        // Collaboration permission
        $permission = \App\Models\SurveyPermission::where('survey_id', $survey->id)
            ->where('user_id', $user->id)
            ->first();

        // Assume any permission row means they can at least view/edit within project scope
        // If finer granularity is needed, check $permission->role or $permission->permissions
        if ($permission) {
            return true;
        }

        // Group membership
        $isGroupMember = \App\Models\SurveyGroup::where('survey_id', $survey->id)
            ->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })->exists();
        if ($isGroupMember) {
            return true;
        }

        // Organization ownership
        if ($survey->organization_id && $user->organization && (int) $survey->organization_id === (int) $user->organization->id) {
            return true;
        }

        // Independent ownership
        if ($survey->independent_id && $user->independent && (int) $survey->independent_id === (int) $user->independent->id) {
            return true;
        }

        return false;
    }
}
