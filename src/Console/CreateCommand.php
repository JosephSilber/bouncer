<?php

namespace Silber\Bouncer\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Silber\Bouncer\BouncerFacade;
use Silber\Bouncer\Database\Role;

/**
 * Class CreateCommand
 * @package Silber\Bouncer\Console
 */
class CreateCommand extends Command
{
    /**
     * @var array $options
     */
    private $options = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bouncer:create
                            {ability : Ability you want to create}
                            {--r|role=* : Role(s) assign with this ability}
                            {--s|scope=* : Scope(s) this ability will under}
                            {--f|forbid : Forbid Role(s) to this ability}
                            {--m|model= : Entity path for this ability (optional); N.B: Replace (\) with (/) as CLI use \ to escape}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new ability , assign to role(s) under scope(s)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->options = $this->getComputedOptions();

        try {
            //only scope no role inputs
            if (empty($this->options['role']) && !empty($this->options['scope'])):
                $this->warn('Without Role(s) scope will have no effect');

                foreach ($this->options['scope'] as $scope_id) :
                    $this->info("Bouncer scope set to {$scope_id}");
                    BouncerFacade::scope()->to($scope_id);
                    $this->createAbility();
                endforeach;

            //only roles no scope inputs
            elseif (!empty($this->options['role']) && empty($this->options['scope'])):
                $this->createAbilityAndAssignToRoles();

            //both roles and scope as inputs
            elseif (!empty($this->options['role']) && !empty($this->options['scope'])):
                $this->createAbilityAndAssignRoleWithScope();

            //just create ability
            else :
                $this->createAbility();

            endif;

            return Command::SUCCESS;

        } catch (Exception $exception) {

            $this->error($exception->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get the trimmed and cleaned option
     *
     * @return array
     */
    private function getComputedOptions(): array
    {
        $result = [];

        foreach ($this->options() as $firstKey => $firstOption):
            if (is_array($firstOption) === true) :
                foreach ($firstOption as $secondKey => $secondOption) :
                    $result[$firstKey][$secondKey] = $this->trim($secondOption);
                endforeach;
            else:
                $result[$firstKey] = $this->trim($firstOption);
            endif;
        endforeach;

        return $result;
    }

    /**
     * trim equal sign from cli options
     *
     * @param mixed $data
     * @return mixed
     */
    private function trim($data)
    {
        return (is_string($data))
            ? trim($data, "=")
            : $data;
    }

    /**
     * Generate ability title from ability name
     *
     * @param string|null $ability default is ability argument
     * @return string
     */
    private function formatAbilityTitle(string $ability = null): string
    {
        $string = $ability ?? $this->ability();

        return ucfirst(str_replace('-', ' ', $string));
    }

    /**
     * Return the ability as lowercase slug format from ability argument
     *
     * @return string
     */
    private function ability(): string
    {
        return trim(
            strtolower(
                Str::slug(
                    $this->argument('ability')
                )
            )
        );
    }

    /**
     * Create plain permission ability
     *
     * @return mixed
     */
    private function createAbility()
    {
        $this->info("Finding/Creating a ability : {$this->ability()}.");
        return BouncerFacade::ability()->firstOrCreate([
            'name' => $this->ability(),
            'title' => $this->formatAbilityTitle()
        ]);
    }

    /**
     * Assigned ability to role with or without scope
     *
     * @param Role $role
     * @param null $scope
     * @return mixed
     */
    private function assignAbilityToRole(Role $role, $scope = null)
    {
        if ($scope != null):
            $this->info("Bouncer scope set to {$scope}");
            BouncerFacade::scope()->to($scope);
        endif;

        $this->info("Assigning permission {$this->ability()} to {$role->name}");

        return (isset($this->options['forbid']) && $this->options['forbid'] === true)
            ? BouncerFacade::forbid($role)->to($this->ability(), $this->model())
            : BouncerFacade::allow($role)->to($this->ability(), $this->model());
    }

    /**
     * create ability and set to roles scope empty
     *
     * @return void
     */
    public function createAbilityAndAssignToRoles()
    {
        $condition['scope'] = null;

        foreach ($this->options['role'] as $role_id) :
            (is_numeric($role_id) === true)
                ? $condition['id'] = $role_id
                : $condition['name'] = $role_id;

            $role = Role::where($condition)->first();

            if ($role instanceof Role)
                $this->assignAbilityToRole($role);

        endforeach;
    }

    /**
     * create ability and set to roles with specific scopes
     * @return void
     */
    private function createAbilityAndAssignRoleWithScope()
    {
        foreach ($this->options['scope'] as $scope_id) :
            foreach ($this->options['role'] as $role_id) :

                $role = Role::where(['id' => $role_id, 'scope' => $scope_id])->first();

                if ($role instanceof Role)
                    $this->assignAbilityToRole($role, $scope_id);

            endforeach;
        endforeach;
    }

    /**
     * Get the model namespaced value from options
     * with slash escape fixed
     *
     * @return string|string[]|null
     */
    private function model()
    {
        return (isset($this->options['model']) && strlen($this->options['model']) > 0)
            ? str_replace("/", "\\", $this->options['model'])
            : null;
    }
}
