<div class="space-y-4">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Member</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Joined</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($this->members as $member)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <img src="{{ $member['avatar'] }}" alt="{{ $member['name'] }}" class="w-10 h-10 rounded-full">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $member['name'] }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $member['email'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($member['can_edit'])
                                <select wire:change="updateRole({{ $member['user_id'] }}, $event.target.value)" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                                    <option value="admin" {{ $member['role'] === 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="member" {{ $member['role'] === 'member' ? 'selected' : '' }}>Member</option>
                                    <option value="viewer" {{ $member['role'] === 'viewer' ? 'selected' : '' }}>Viewer</option>
                                </select>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    {{ $member['role'] === 'owner' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                                    {{ $member['role'] === 'admin' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                    {{ $member['role'] === 'member' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : '' }}">
                                    {{ ucfirst($member['role']) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $member['joined_at'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            @if($member['can_edit'])
                                <button wire:click="removeMember({{ $member['user_id'] }})" wire:confirm="Are you sure you want to remove this member?" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                    Remove
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
