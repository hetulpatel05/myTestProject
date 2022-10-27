<table class="table" id="tbl_list">
    <tbody>
        @forelse($profileitems as $profile)
            <tr data-profilename="{{ $profile->name }}" data-profileid="{{ $profile->id }}" style="cursor:pointer"
                class="profile_account">
                <td>
                    <b>{{ $profile->name }}</b><p>{{ $profile->id }}</p>                    
                </td>
            </tr>
        @empty
            <p>No profiles found for this user.</p>
        @endforelse
    </tbody>
</table>