<?php

namespace App\Console\Commands;

use App\Models\GeneralSettings;
use App\Models\ReportIssues;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RemoveReportIssueChat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove_report_issue_chat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove Report Issue Chat From Firebase';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /* Remove Report Issue Chat */
        $report_issue_settings =  request()->get('general_settings');
        if(isset($report_issue_settings->report_chat_history_delete) && $report_issue_settings->report_chat_history_delete == 1){
            $report_issue = ReportIssues::where('status', 2)->get();
            $remove_after_days = $report_issue_settings->chat_deletion_days_after_issue_resolution ?? 0;

            if(isset($report_issue) && count($report_issue) > 0){
                foreach ($report_issue as $issue) {
                    try {
                        $to = Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($issue->resolved_on)));
                        $from = Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime(now())));
                        $diff_in_days = $to->diffInDays($from);
                        if (abs($diff_in_days) >= $remove_after_days) {
                            $path = public_path('assets/images/report-issue-images/' . $issue->reference_no.'-' . $issue->id);
                            if (File::exists($path)) {
                                // deleting chat from firebase
                                File::deleteDirectory($path);
                            }
                            (new FirebaseService())->deleteOrderChat($issue->reference_no,$issue->id,"report_issue_chat");
                        }
                    } catch (\Exception $e) {
                        \Log::info("error");
                        \Log::info($e);
                    }
                }
            }
        }
    }
}
