<?php

declare(strict_types=1);

return [
    // Page titles
    'deployment_approvals_title' => 'Deployment Approvals',
    'deployment_activity_title' => 'Deployment Activity',
    'projects_management_title' => 'Projects Management',

    // Page descriptions
    'deployment_approvals_description' => 'Review and approve pending deployments to production environments.',
    'deployment_activity_description' => 'Explore historical deployments, filter by status or project, and jump straight into detailed logs with a click.',
    'projects_management_description' => 'Manage and deploy your applications with ease',

    // Modal titles
    'approve_deployment_title' => 'Approve Deployment',
    'reject_deployment_title' => 'Reject Deployment',

    // Modal messages
    'approve_deployment_confirmation' => 'Are you sure you want to approve this deployment? This will trigger the deployment process.',
    'reject_deployment_message' => 'Please provide a reason for rejecting this deployment request.',

    // Empty states
    'no_approvals_found' => 'No approvals found',
    'no_approvals_description' => 'There are no deployment approvals matching your criteria.',
    'no_deployments_found' => 'No deployments found',
    'no_projects_found' => 'No projects found',

    // Success messages
    'deployment_approved' => 'Deployment approved successfully',
    'deployment_rejected' => 'Deployment rejected successfully',
    'project_created' => 'Project created successfully',
    'project_updated' => 'Project updated successfully',

    // Error messages
    'deployment_approval_failed' => 'Failed to approve deployment',
    'deployment_rejection_failed' => 'Failed to reject deployment',
    'project_creation_failed' => 'Failed to create project',

    // Info messages
    'required_field' => 'This field is required',
    'optional_field' => 'Optional',

    // Stats labels
    'projects_count' => ':count Projects',
    'running_count' => ':count Running',
    'building_count' => ':count Building',
    'stopped_count' => ':count Stopped',
];
