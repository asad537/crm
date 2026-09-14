<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\CrmWebsite;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    private function guard()
    {
        $user = \Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isSuperAdmin())) abort(403, 'Only Admin / CEO can manage websites.');
        return $user;
    }

    public function index()
    {
        $this->guard();
        $websites = CrmWebsite::orderBy('name')->get();
        return view('crm.websites.index', compact('websites'));
    }

    public function store(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'name'  => 'required|string|max:120|unique:crm_websites,name',
            'color' => 'nullable|string|max:16',
        ]);
        CrmWebsite::create([
            'name' => trim($data['name']),
            'color' => $data['color'] ?: '#6c5ce7',
            'is_active' => true,
            'created_by' => \Auth::guard('crm')->id(),
        ]);
        return back()->with('success', 'Website "'.$data['name'].'" added.');
    }

    public function update(Request $request, $id)
    {
        $this->guard();
        $website = CrmWebsite::findOrFail($id);
        $data = $request->validate([
            'name'  => 'required|string|max:120|unique:crm_websites,name,'.$id,
            'color' => 'nullable|string|max:16',
        ]);
        $website->update(['name' => trim($data['name']), 'color' => $data['color'] ?: $website->color]);
        return back()->with('success', 'Website updated.');
    }

    public function toggle($id)
    {
        $this->guard();
        $website = CrmWebsite::findOrFail($id);
        $website->update(['is_active' => !$website->is_active]);
        return back()->with('success', 'Website '.($website->is_active ? 'activated' : 'deactivated').'.');
    }

    public function destroy($id)
    {
        $this->guard();
        CrmWebsite::findOrFail($id)->delete();
        return back()->with('success', 'Website deleted.');
    }
}
