<table class="table">
    <tbody>
        @forelse($profileitems as $profile)
            <tr data-profilename="{{ $profile->name }}" data-profileid="{{ $profile->id }}" style="cursor:pointer"
                class="profile_account">
                <td>
                    <div><b>{{ $profile->name }}</b> - <p>{{ $profile->id }}</p>
                    </div>
                </td>
            </tr>
        @empty
            <p>No profiles found for this user.</p>
        @endforelse
    </tbody>
</table>